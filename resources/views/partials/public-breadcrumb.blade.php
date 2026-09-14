@props(['title', 'current'])

<div class="bread-crumb-bar">
	<div class="container">
		<div class="row align-items-center inner-banner">
			<div class="col-md-12 col-12 text-center">
				<div class="breadcrumb-list">
					<h2>{{ $title }}</h2>
					<nav aria-label="breadcrumb" class="page-breadcrumb">
						<ol class="breadcrumb">
							<li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
							<li class="breadcrumb-item" aria-current="page">{{ $current ?? $title }}</li>
						</ol>
					</nav>
				</div>
			</div>
		</div>
	</div>
</div>
