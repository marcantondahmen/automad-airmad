<@ Airmad/Airmad {
	base: @{ :base },
	table: 'Design projects',
	view: 'All projects',
	linked: 'Client => Clients',
	filters: 'Client, Category',
	template: '/packages/airmad/airmad/snippets/example.handlebars',
	limit: 8,
	prefix: ':example'
} @>

@{ :exampleOutput }

<ul class="uk-pagination">
	<@ if @{ ?Page } > 1 @>
		<li><a href="?<@ queryStringMerge { Page: @{ ?Page | -1 } } @>">←</a></li>
	<@ end @>
	<@ for @{ :examplePage | -4 } to @{ :examplePage | +4 } @>
		<@ if @{ :i } > 0 and @{ :i } <= @{ :examplePages } @>
			<li><a href="?<@ queryStringMerge { Page: @{ :i } } @>" <@ if @{ ?Page | def(1) } = @{ :i } @>
				class="uk-active"
			<@ end @>>@{:i}</a></li>
		<@ end @>
	<@ end @>
	<@ if @{ ?Page } < @{ :examplePages } @>
		<li><a href="?<@ queryStringMerge { Page: @{ ?Page | +1 } } @>">→</a></li>
	<@ end @>
</ul>