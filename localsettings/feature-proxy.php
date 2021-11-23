<?php

// Allow proxying content from production

$wgMFContentProviderClass = "MobileFrontendContentProviders\\MwApiContentProvider";
$wgMFContentProviderTryLocalContentFirst = true;
$wgMFMwApiContentProviderBaseUri = "https://$wgLanguageCode.wikipedia.org/w/api.php";

// Enable proxying for Page previews if enabled.

$wgPopupsGateway = 'restbaseHTML';
$wgPopupsRestGatewayEndpoint = "https://$wgLanguageCode.wikipedia.org/api/rest_v1/page/summary/";

// Enable proxying for RelatedArticles if enabled.

$wgRelatedArticlesUseCirrusSearchApiUrl = "https://$wgLanguageCode.wikipedia.org/w/api.php";
$wgRelatedArticlesUseCirrusSearch = true;
$wgRelatedArticlesDescriptionSource = 'wikidata';

// Vector search proxying

$wgVectorSearchHost = "$wgLanguageCode.wikipedia.org";
