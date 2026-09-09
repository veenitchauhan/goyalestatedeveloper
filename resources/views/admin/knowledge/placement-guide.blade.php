<details class="placement-guide">
    <summary>Where will my content appear?</summary>
    <p>The homepage keeps its three original company FAQs. Your content appears separately in the dark “Ideas, insights & answers” section below them.</p>
    <ul>
        <li><strong>FAQs:</strong> every published question appears automatically in the dark homepage section and on the FAQs page.</li>
        <li><strong>Blog:</strong> published posts appear on the Blog page. Select “Also show on homepage” to include a post in the dark section.</li>
        <li><strong>Knowledge Bank:</strong> published guides appear on the Knowledge Bank page. Select “Also show on homepage” to include a guide in the dark section.</li>
    </ul>
    <p>The title and short answer appear in the homepage accordion. The detailed answer opens through “Read more”. Lower sort numbers appear first.</p>
    @if(\App\Services\ContentPublisher::immediate())
        <p>Click Save to publish your changes immediately. Use Hide in the publication controls to remove an item from the website.</p>
    @endif
</details>
