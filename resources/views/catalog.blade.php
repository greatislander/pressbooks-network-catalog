@php(get_header())

<main>
	<div class="hero">
		<div class="hero-content">
			<h1>{{ get_the_title() }}</h1>
			<div>
				{!! wp_kses_post(get_the_content()) !!}
			</div>
		</div>
	</div>

	<div class="network-catalog">
		<aside class="side-filters">
			@include('PressbooksNetworkCatalog::partials.sidebar-filters')
		</aside>

		<div>
			@include('PressbooksNetworkCatalog::partials.search-box')

			<div class="book-cards">
				@forelse( $books as $book )
					@include('PressbooksNetworkCatalog::partials.book-card')
				@empty
					<p>No books have been added to the catalog yet.</p>
				@endforelse
			</div>
		</div>
	</div>
</main>

@php(get_footer())
