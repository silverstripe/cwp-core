<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>$Title</title>
	<link href="$Link" rel="self" />
	<link href="$BaseHref" />
	<id>$Link.XML</id>
	<updated><% loop Entries %><% if First %>$LastEdited.Rfc3339<% end_if %><% end_loop %></updated>
	<author><name><% if Author %>$Author.XML<% end_if %></name></author>
	<% loop $Entries %>
		<entry>
			<title type="html">$Title.XML</title>
			<link href="$AbsoluteLink" />
				<content type="html">
				<% if Content %>
					$Content.AbsoluteLinks.XML
				<% end_if %>
			</content>
			<author><name><% if Author %>$Author.XML<% end_if %></name></author>
			<updated>$LastEdited.Rfc3339</updated>
			<id>$AbsoluteLink.XML</id>
		</entry>
	<% end_loop %>
</feed>
