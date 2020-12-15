<@ Airmad/Airmad {
	base: @{ :base },
	table: 'Design projects',
	view: 'All projects',
	linked: 'Client => Clients',
	filters: 'Client, Category',
	template: '/packages/airmad/airmad/snippets/example.handlebars',
	limit: 1000,
	prefix: ':example'
} @>


@{ :exampleMemory | /1024 | /1024 }<br>
@{ :exampleOutput }
