@use('Illuminate\Support\Str')

<x-app title="FAQ - NextHire">
	@include('partials.public-breadcrumb', ['title' => 'Frequently Asked Questions'])

	<section class="faq-section-three">
		<div class="container">
			<div class="row justify-content-center mb-4">
				<div class="col-lg-8 text-center aos" data-aos="fade-up">
					<h2>How Can We Help?</h2>
					<p>Find answers to common questions about using NextHire as a job seeker, employer, or organization.</p>
				</div>
			</div>

			@php
				$faqSections = [
					'' => [
						['q' => 'How do I create an account on NextHire?', 'a' => 'Click "Get Started" or "Register" on any page to create your applicant account. Complete your profile with accurate personal details and upload required documents to begin applying for jobs.'],
						['q' => 'How do I apply for a job?', 'a' => 'Browse available jobs on the Jobs page, select a listing to view details, and click "Apply." You will be guided through the application form where you can review your profile and submit supporting documents.'],
						['q' => 'Can I track my application status?', 'a' => 'Yes. Once you submit an application, you can track its status from your applicant dashboard. You will receive notifications when your application is reviewed, approved, or rejected.'],
						['q' => 'Why is my account showing as pending?', 'a' => 'New accounts require verification by an administrator before full access is granted. This ensures platform security and data integrity. You will be notified once your account is activated.'],
						['q' => 'What documents do I need to apply?', 'a' => 'Required documents vary by job listing. Common requirements include a valid ID, academic credentials, professional certifications, and a resume. The application form will specify what is needed for each role.'],
						['q' => 'How does job approval work?', 'a' => 'All job listings submitted by employers go through an administrative review process. Administrators verify the listing details and compliance before approving it for public visibility.'],
						['q' => 'Is NextHire free to use?', 'a' => 'Account registration and job applications are free for job seekers. Employer and organizational features may be subject to service agreements. Contact our team for details on enterprise plans.'],
			
					],
					
					
				];
				$accordionIndex = 0;
			@endphp

			@foreach($faqSections as $sectionTitle => $questions)
			<div class="row mb-5">
				<div class="col-12">
					<h3 class="mb-4 aos" data-aos="fade-up">{{ $sectionTitle }}</h3>
				</div>
				<div class="col-12">
					<div class="faq" id="accordion-{{ Str::slug($sectionTitle) }}">
						@foreach($questions as $faq)
						@php $accordionIndex++; @endphp
						<div class="card aos" data-aos="fade-up">
							<div class="card-header faq-title" id="heading-{{ $accordionIndex }}">
								<h4 class="mb-0">
									<a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $accordionIndex }}" aria-expanded="false" aria-controls="collapse-{{ $accordionIndex }}">
										{{ $faq['q'] }}
									</a>
								</h4>
							</div>
							<div id="collapse-{{ $accordionIndex }}" class="collapse" aria-labelledby="heading-{{ $accordionIndex }}" data-bs-parent="#accordion-{{ Str::slug($sectionTitle) }}">
								<div class="card-body">
									{{ $faq['a'] }}
								</div>
							</div>
						</div>
						@endforeach
					</div>
				</div>
			</div>
			@endforeach
		</div>
	</section>

	@include('partials.public-cta')
</x-app>
