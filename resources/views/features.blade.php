<x-app title="Features - NextHire">
	@include('partials.public-breadcrumb', ['title' => 'Platform Features'])

	<section class="section about">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-lg-10 text-center aos" data-aos="fade-up">
					<div class="about-content">
						<h2>Everything You Need for Professional Recruitment</h2>
						<p>NextHire combines powerful recruitment tools with an intuitive user experience, giving administrators, employers, and applicants the features they need to hire and apply efficiently.</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="section projects">
		<div class="container">
			<div class="row row-gap">
				@foreach([
					['icon' => 'great1.svg', 'title' => 'Job Posting & Management', 'text' => 'Create, edit, and manage job listings with approval workflows that ensure every vacancy meets organizational standards before going live.'],
					['icon' => 'great2.svg', 'title' => 'Application Tracking', 'text' => 'Track every application from submission through review, shortlisting, approval, and rejection with full status visibility.'],
					['icon' => 'great3.svg', 'title' => 'Document Verification', 'text' => 'Applicants upload credentials and supporting documents through a secure portal. Recruiters review and approve documents before proceeding.'],
					['icon' => 'great4.svg', 'title' => 'Role-Based Access Control', 'text' => 'Separate dashboards and permissions for administrators, employers, and applicants ensure each user sees only what they need.'],
					['icon' => 'achievement-1.svg', 'title' => 'Candidate Shortlisting', 'text' => 'Employers and administrators can shortlist qualified candidates, manage approved and rejected pools, and access detailed applicant profiles.'],
					['icon' => 'achievement-2.svg', 'title' => 'Account Verification', 'text' => 'New accounts go through a verification process to maintain platform integrity and protect all users.'],
					['icon' => 'achievement-3.svg', 'title' => 'Real-Time Notifications', 'text' => 'Stay informed with notifications for application updates, account status changes, document reviews, and job approvals.'],
					['icon' => 'achievement-4.svg', 'title' => 'Profile Management', 'text' => 'Comprehensive applicant profiles with personal details, qualifications, and document storage support complete and accurate applications.'],
				] as $index => $feature)
				<div class="col-xl-3 col-md-6 aos d-flex" data-aos="zoom-in" data-aos-duration="{{ 1000 + ($index % 4 * 500) }}">
					<div class="feature-items d-flex align-items-center justify-content-center flex-column">
						<div class="feature-icon">
							<img src="{{ asset('assets/img/icon/'.$feature['icon']) }}" class="img-fluid" alt="{{ $feature['title'] }}">
						</div>
						<div class="feature-content course-count text-center">
							<h3>{{ $feature['title'] }}</h3>
							<p>{{ $feature['text'] }}</p>
						</div>
					</div>
				</div>
				@endforeach
			</div>
		</div>
	</section>

	<section class="section review">
		<div class="container">
			<div class="row">
				<div class="col-lg-6">
					<div class="aos" data-aos="fade-up">
						<div class="demand-professional">
							<h2>Designed for Security and Compliance</h2>
							<p>Recruitment involves sensitive personal and organizational data. NextHire is built with security at its core.</p>
						</div>
						@foreach([
							'Secure authentication with account status management (active, pending, suspended).',
							'Protected document storage with preview and download controls for authorized users.',
							'Administrative oversight with user verification, job approval, and account management tools.',
						] as $point)
						<div class="demand-post-job align-items-start">
							<div class="demand-post-img">
								<img src="{{ asset('assets/img/icon/checks.svg') }}" alt="Check" class="img-fluid">
							</div>
							<div class="demand-content">
								<p>{{ $point }}</p>
							</div>
						</div>
						@endforeach
					</div>
				</div>
				<div class="col-lg-6">
					<div class="work-set-image">
						<div class="recent-pro-img aos" data-aos="zoom-in" data-aos-duration="1000">
							<img src="{{ asset('assets/img/blog/abt3.png') }}" alt="NextHire security features" class="img-fluid">
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	@include('partials.public-cta')
</x-app>
