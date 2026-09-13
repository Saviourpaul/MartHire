<x-app>
    <div class="content">
				<div class="container-fluid">					
					<div class="row">
						<div class="col-md-8 offset-md-2">
							
							<!-- Page Not Found -->
							<div class="empty-content text-center">
								<img src="{{ asset('assets/img/404.png') }}" alt="" class="img-fluid">
                                <br>
								<h2>Page not found</h2>
                                <img src="{{ asset('assets/images/logo.png') }}" alt="404" width="400"   class="error-image" >
								<p>We can’t seem to find the page you are looking for!.</p>
								<div class="btn-item">
									<a class="btn get-btn" href="{{ route('home') }}">Go To Home <i class="feather-arrow-right ms-2"></i></a>
									<a class="btn courses-btn" href="javascript:history.back()">Back <i class="feather-arrow-right ms-2"></i></a>
								</div>
							</div>
                            
							<!-- / Page Not Found -->
								
						</div>
					</div>
				</div>
                
			</div>
</x-app>