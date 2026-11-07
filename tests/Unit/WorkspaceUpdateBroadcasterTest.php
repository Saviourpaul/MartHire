<?php

use App\Events\WorkspaceUpdated;
use App\Services\WorkspaceUpdateBroadcaster;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class);

test('workspace broadcasts are synchronous, allow-listed, and dispatched only after commit', function () {
    config(['broadcasting.default' => 'null']);
    Queue::fake();
    $broadcasts = [];
    Event::listen(WorkspaceUpdated::class, function (WorkspaceUpdated $event) use (&$broadcasts): void {
        $broadcasts[] = $event;
    });
    DB::beginTransaction();

    app(WorkspaceUpdateBroadcaster::class)->organization(27, ['jobs', 'secret', 'jobs'], 'A job was suspended.', 'warning');

    expect($broadcasts)->toBeEmpty();
    DB::commit();

    Queue::assertNothingPushed();
    expect($broadcasts)->toHaveCount(1)
        ->and($broadcasts[0])->toBeInstanceOf(ShouldBroadcastNow::class)
        ->and($broadcasts[0]->broadcastOn()[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($broadcasts[0]->broadcastOn()[0]->name)->toBe('private-organizations.27')
        ->and($broadcasts[0]->broadcastWith())->toBe([
            'topics' => ['jobs'],
            'message' => 'A job was suspended.',
            'type' => 'warning',
        ]);
});

test('applicant workspace invalidations use the applicant private channel', function () {
    config(['broadcasting.default' => 'null']);
    Queue::fake();
    $broadcasts = [];
    Event::listen(WorkspaceUpdated::class, function (WorkspaceUpdated $event) use (&$broadcasts): void {
        $broadcasts[] = $event;
    });

    app(WorkspaceUpdateBroadcaster::class)->applicant(41, ['applications', 'interviews', 'notifications', 'private-data']);

    Queue::assertNothingPushed();
    expect($broadcasts)->toHaveCount(1)
        ->and($broadcasts[0]->broadcastOn()[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($broadcasts[0]->broadcastOn()[0]->name)->toBe('private-App.Models.User.41')
        ->and($broadcasts[0]->broadcastWith())->toBe([
            'topics' => ['applications', 'interviews', 'notifications'],
            'message' => null,
            'type' => 'info',
        ]);
});
