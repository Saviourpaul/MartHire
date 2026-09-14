@use('Illuminate\Support\Str')

<x-app :title="'Jobs - NextHire'">
	<!-- Breadcrumb -->
	<div class="bread-crumb-bar">
		<div class="container">
			<div class="row align-items-center inner-banner">
				<div class="col-md-12 col-12 text-center">
					<div class="breadcrumb-list">
						<h2>Jobs</h2>
						<nav aria-label="breadcrumb" class="page-breadcrumb">
							<ol class="breadcrumb">
								<li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
								<li class="breadcrumb-item" aria-current="page">Jobs</li>
							</ol>
						</nav>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- /Breadcrumb -->

	<!-- Page Content -->
	<div class="content">
		<div class="container">
			<div class="row">
				<div class="col-md-12 col-sm-12 col-12 mx-auto text-center">
					<div class="section-header aos" data-aos="fade-up">
						<h2 class="header-title">All Jobs</h2>
						<p>Browse all available job opportunities</p>
					</div>
				</div>
			</div>

			<div class="row blog-grid-row g-4">
				@forelse($jobs as $job)
				<div class="col-xl-4 col-md-6 col-sm-12 d-flex">
					<!-- Job Post -->
					<div class="blog grid-blog aos flex-fill w-100" data-aos="fade-up">
						<div class="blog-image">
							<a href="{{ route('job-details', $job) }}"><img class="img-fluid w-100" src="{{ $job->logoUrl() }}" alt="{{ $job->company }} logo" style="height: 220px; object-fit: contain;"></a>
						</div>
						<div class="blog-content d-flex flex-column h-100">
							<ul class="entry-meta meta-item mb-2">
								<li class="mb-0">
									<div class="post-author">
										<a href="{{ route('job-details', $job) }}"><span>{{ $job->company }}</span></a>
									</div>
								</li>
								<li><i class="feather-calendar me-1"></i> {{ $job->created_at->format('d M Y') }}</li>
							</ul>
							<h3 class="blog-title"><a href="{{ route('job-details', $job) }}">{{ $job->title }}</a></h3>
							<p class="mb-0 flex-grow-1">{{ Str::limit(strip_tags($job->description), 150) }}</p>
							<div class="blog-read mt-auto">
								<a href="{{ route('job-details', $job) }}">Read More <i class="fas fa-arrow-right ms-1"></i></a>
							</div>
						</div>
					</div>
					<!-- /Job Post -->
				</div>
				@empty
				<div class="col-12 text-center py-5">
					<h4>No jobs found</h4>
					<p>Check back later for new job opportunities.</p>
				</div>
				@endforelse
			</div>

			@if($jobs->hasPages())
			<div class="row mt-4">
				<div class="col-12 d-flex justify-content-center">
					{{ $jobs->links('pagination::bootstrap-5') }}
				</div>
			</div>
			@endif
		</div>
	</div>
	<!-- /Page Content -->
</x-app>
