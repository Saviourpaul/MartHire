<?php

use App\Support\JobDescription;

it('preserves supported formatting, typography, links, tables and image attributes', function () {
    $html = (new JobDescription)->toHtml('<h1>Role</h1><h6>Details</h6><p style="text-align: center; position:fixed"><span style="color:#123456; font-family:Georgia; font-size:24px; line-height:1.5">Join <strong>us</strong></span><em>today</em><u>now</u><s>old</s><sub>2</sub><sup>3</sup><mark style="background-color:#fef08a">Highlight</mark><code>PHP</code></p><blockquote>Quote</blockquote><pre><code>Code</code></pre><ol start="2"><li>Step</li></ol><ul><li>Benefit</li></ul><hr><table><tbody><tr><th colspan="2">Header</th></tr><tr><td>Cell</td></tr></tbody></table><a href="https://example.com" title="Careers">Apply</a><img src="https://example.com/team.png" alt="Our team" title="Team" width="50%" data-align="center">');

    expect($html)->toContain('<h1>Role</h1>', '<h6>Details</h6>', 'text-align: center', 'color: #123456', 'font-family: Georgia', 'font-size: 24px', 'line-height: 1.5', '<strong>us</strong>', '<em>today</em>', '<u>now</u>', '<s>old</s>', '<sub>2</sub>', '<sup>3</sup>', 'background-color: #fef08a', '<code>PHP</code>', '<blockquote>Quote</blockquote>', '<ol start="2">', '<table>', 'colspan="2"', 'href="https://example.com"', 'rel="noopener noreferrer nofollow"', 'width="50%"', 'data-align="center"', 'alt="Our team"', 'loading="lazy"', 'referrerpolicy="no-referrer"')
        ->not->toContain('position');
});

it('removes unsafe markup, URLs, CSS and image attributes', function (string $unsafe) {
    $html = (new JobDescription)->toHtml('<p>Safe text</p>'.$unsafe);

    expect($html)->toContain('Safe text')
        ->not->toMatch('/<script|<iframe|<svg|<math|<object|<form|onerror|onload|onclick|javascript:|vbscript:|data:text|data:image|expression\(|url\(|position:|srcdoc|id="attack"|class="attack"/i');
})->with([
    'scripts and handlers' => '<script>alert(1)</script><p onclick="alert(1)" id="attack" class="attack">Text</p><img src="https://example.com/a.png" onerror="alert(1)">',
    'unsafe links' => '<a href="jav&#x61;script:alert(1)">Link</a><a href="data:text/html,attack">Data</a><a href="vbscript:attack">Vb</a>',
    'unsafe images' => '<img src="javascript:alert(1)"><img src="data:image/svg+xml,attack"><img src="http://example.com/a.png" srcset="attack 2x">',
    'CSS injection' => '<span style="color:expression(alert(1));background-image:url(https://example.com);position:fixed;font-family:evil;">Text</span>',
    'embedded documents' => '<iframe srcdoc="attack"></iframe><object data="https://example.com"></object><form><input autofocus onfocus="alert(1)"></form>',
    'foreign content' => '<svg><foreignObject><p onload="alert(1)">Text</p></foreignObject></svg><math><mtext><img src=x onerror=alert(1)></mtext></math>',
    'malformed markup' => '<table><svg><style><img src=x onerror=alert(1)></style></svg></table><p style="color:red;position:fixed">Text</p>',
]);

it('preserves Unicode and line breaks in existing plain descriptions', function () {
    expect((new JobDescription)->toHtml("Join Marthire — Lagos.\n\nSalary < 500 & benefits."))
        ->toBe("<p>Join Marthire — Lagos.<br />\n<br />\nSalary &lt; 500 &amp; benefits.</p>");
    expect((new JobDescription)->toHtml('<p>Développeur — Lagos ₦</p>'))
        ->toBe('<p>Développeur — Lagos ₦</p>');
});

it('detects descriptions without readable role text', function (string $description, bool $expected) {
    expect((new JobDescription)->hasText($description))->toBe($expected);
})->with([
    'empty' => ['', false],
    'empty paragraph' => ['<p><br></p>', false],
    'invisible text' => ['<p>&nbsp; &#8203;</p>', false],
    'script only' => ['<script>alert(1)</script>', false],
    'image only' => ['<img src="https://example.com/a.jpg">', false],
    'formatted text' => ['<h2>Join us</h2>', true],
    'plain text' => ['Build Laravel applications.', true],
]);

it('is idempotent for supported formatted descriptions', function () {
    $formatter = new JobDescription;
    $html = $formatter->toHtml('<h2>Responsibilities</h2><p style="text-align:right;color:#123456">Build APIs.</p><img src="https://example.com/team.png" width="50%" data-align="right"><a href="https://example.com">Apply</a>');

    expect($formatter->toHtml($html))->toBe($html);
});
