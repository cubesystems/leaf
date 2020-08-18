{strip}
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	{foreach from=$list item=object}
   <url>
      <loc>{$object.id|orp}</loc>
      <lastmod>{$object.last_edit|date_format:"%Y-%m-%d"}</lastmod>
   </url>
	{/foreach}
</urlset>
{/strip}