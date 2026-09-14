<x-app :title="$job->title . ' - NextHire'">
			<!-- Breadcrumb -->
			<div class="bread-crumb-bar">
				<div class="container">
					<div class="row align-items-center inner-banner">
						<div class="col-md-12 col-12 text-center">
							<div class="breadcrumb-list">
								<h2>Job Details</h2>
								<nav aria-label="breadcrumb" class="page-breadcrumb">
									<ol class="breadcrumb">
										<li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
										<li class="breadcrumb-item" aria-current="page">{{ $job->title }}</li>
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
						<div class="col-lg-8 col-md-12">
							<div class="company-detail-block pt-0">
								<div class="company-detail">
									<div class="company-detail-image">
										<img src="{{ $job->logoUrl() }}" class="img-fluid" alt="{{ $job->company }} logo">
									</div>
									<div class="company-title">
										<p>{{ $job->company }}</p>
										<h4>{{ $job->title }}</h4>
									</div>
								</div>
								<div class="company-address">
									<ul>
										
										<li>
											<i class="feather-calendar"></i>{{ $job->created_at->format('d F Y') }}
										</li>
										<li>
											<i class="feather-calendar"></i>Due: {{ $job->due_date->format('d F Y') }}
										</li>
										<li>
											<i class="feather-edit-2"></i>Status: {{ $job->status->label() }}
										</li>
									</ul>
								</div>
								<!--div class="project-proposal-detail">
									<ul>
										<li>
											<div class="proposal-detail-img">
												<img src="{{ asset('assets/img/icon/computer-line.svg') }}" alt="icons">
											</div>
											<div class="proposal-detail text-capitalize">
												<span class=" d-block">Freelancer Type</span>
												<p class="mb-0">Full Time</p>
											</div>
										</li>
										<li>
											<div class="proposal-detail-img">
												<img src="{{ asset('assets/img/icon/time-line.svg') }}" alt="icons">
											</div>
											<div class="proposal-detail text-capitalize">
												<span class=" d-block">Project Type</span>
												<p class="mb-0">Hourly</p>
											</div>
										</li>
										<li>
											<div class="proposal-detail-img">
												<img src="{{ asset('assets/img/icon/time-line.svg') }}" alt="icons">
											</div>
											<div class="proposal-detail text-capitalize">
												<span class=" d-block">Project Duration</span>
												<p class="mb-0">10-15 Hours</p>
											</div>
										</li>
										<li>
											<div class="proposal-detail-img">
												<img src="{{ asset('assets/img/icon/user-heart-line.svg') }}" alt="icons">
											</div>
											<div class="proposal-detail text-capitalize">
												<span class=" d-block">Experience</span>
												<p class="mb-0">Basic</p>
											</div>
										</li>
										<li>
											<div class="proposal-detail-img">
												<img src="{{ asset('assets/img/icon/translate-2.svg') }}" alt="icons">
											</div>
											<div class="proposal-detail text-capitalize">
												<span class=" d-block">Languages</span>
												<p class="mb-0">English, Arabic</p>
											</div>
										</li>
										<li>
											<div class="proposal-detail-img">
												<img src="{{ asset('assets/img/icon/translate.svg') }}" alt="icons">
											</div>
											<div class="proposal-detail text-capitalize">
												<span class=" d-block">Language Fluency</span>
												<p class="mb-0">Conversational</p>
											</div>
										</li>
									</ul>
								</div-->
							</div>
							<div class="company-detail-block company-description">
								<h4 class="company-detail-title">Description</h4>
								{!! nl2br(e(strip_tags($job->description))) !!}
							</div>
							
							
							
						</div>
					
						<!-- Apply Button -->
						<div class="col-lg-4 col-md-12 sidebar-right theiaStickySidebar project-client-view">	
							<div class="job-apply-det">
								<div class="job-apply-det-inner">
							@if(! $job->isApproved())
								<p class="text-danger">This job is not currently accepting applications.</p>
								
							@elseif($job->isApproved())
								<h3 class="mb-0">Apply for this job</h3>
								<p>Click the button below to submit your application.</p>
								
								@auth
									@if (auth()->user()->isApplicant())
										@if ($existingApplication)
											<p class="mb-2">You applied for this job on {{ $existingApplication->submitted_at->format('M d, Y') }}.</p>
											<span class="badge {{ $existingApplication->status->badgeClass() }}">{{ $existingApplication->status->label() }}</span>
											<a href="{{ route('client.applications.show', $existingApplication) }}" class="btn btn-primary apply-btn mt-3">View Application</a>
										@else
											<a href="{{ route('applications.create', $job) }}" class="btn btn-primary apply-btn">Apply Now</a>
										@endif
									@else
										<p class="text-muted">Only applicant accounts can apply for jobs.</p>
									@endif
								@else
									<a href="{{ route('applications.create', $job) }}" class="btn btn-primary apply-btn">Apply</a>
								@endauth
								
							@endif
						</div>
						
						
						<!-- /Apply Button -->
						
					</div>
				</div>
			</div>		
			<!-- /Page Content -->
   
</x-app>
