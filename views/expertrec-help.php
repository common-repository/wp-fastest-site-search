<!DOCTYPE html>
<html lang="en">
<head>
	<style type="text/css">

		* {
			box-sizing: border-box;
		}

		*::before,
		*::after {
			box-sizing: border-box;
		}

		body {
			margin: 0;
			padding: 0;
			background: #fff;
			display: flex;
		}

		.container {
			margin: 0 auto;
			padding: 4rem;
			width: 48rem;
		}

		.accordion .accordion-item {
			border-bottom: 1px solid #e5e5e5;
		}

		.accordion .accordion-item button[aria-expanded='true'] {
			border-bottom: 1px solid #f8743b;
		}

		.accordion button {
			position: relative;
			display: block;
			text-align: left;
			width: 100%;
			padding: 1em 0;
			color: #947365;
			font-size: 1.15rem;
			font-weight: 400;
			border: none;
			background: none;
			outline: none;
		}

		.accordion button:hover,
		.accordion button:focus {
			cursor: pointer;
			color: #f8743b;
		}

		.accordion button:hover::after,
		.accordion button:focus::after {
			cursor: pointer;
			color: #f8743b;
			border: 1px solid #f8743b;
		}

		.accordion button .accordion-title {
			padding: 1em 1.5em 1em 0;
		}

		.accordion button .icon {
			display: inline-block;
			position: absolute;
			top: 18px;
			right: 0;
			width: 22px;
			height: 22px;
			border: 1px solid;
			border-radius: 22px;
		}

		.accordion button .icon::before {
			display: block;
			position: absolute;
			content: '';
			top: 9px;
			left: 5px;
			width: 10px;
			height: 2px;
			background: currentColor;
		}

		.accordion button .icon::after {
			display: block;
			position: absolute;
			content: '';
			top: 5px;
			left: 9px;
			width: 2px;
			height: 10px;
			background: currentColor;
		}

		.accordion button[aria-expanded='true'] {
			color: #f8743b;
		}

		.accordion button[aria-expanded='true'] .icon::after {
			width: 0;
		}

		.accordion button[aria-expanded='true'] + .accordion-content {
			opacity: 1;
			max-height: fit-content;
			transition: all 200ms linear;
			will-change: opacity, max-height;
		}

		.accordion .accordion-content {
			opacity: 0;
			max-height: 0;
			overflow: hidden;
			transition: opacity 200ms linear, max-height 200ms linear;
			will-change: opacity, max-height;
		}

		.accordion .accordion-content p {
			font-size: 1rem;
			font-weight: 300;
			margin: 2em 0;
		}
	</style>
</head>
<body>
<div>
	<?php require 'expertrec-page-header.php'; ?>
</div>
<div class="container">
	<h2 style="text-align: center;">Frequently Asked Questions</h2>
	<div class="accordion">
		<div class="accordion-item">
			<button id="accordion-button-1" aria-expanded="false">
				<span class="accordion-title">Knowledge Base</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					You can find solutions and answers at the <a
							href="https://blog.expertrec.com/knowledge-base/?utm_source=wordpress.org"
							rel="nofollow ugc">ExpertRec Knowledge Base</a>.
				</p>
			</div>
		</div>
		<div class="accordion-item">
			<button id="accordion-button-4" aria-expanded="false">
				<span class="accordion-title">Can I use this on my Woocommerce store?</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					Yes, ExpertRec site search does support Woocommerce search. The steps for installation are exactly
					the same. The search bar will intelligently detect Woocommerce and make the neccessary changes to
					get a better search experience suited to eCommerce.
				</p>
			</div>
		</div>
		<div class="accordion-item">
			<button id="accordion-button-2" aria-expanded="false">
				<span class="accordion-title">What is my API Key?</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					A common mistake is to think that your API key is the same as your email ID or web URL. API key is a
					unique identifier that you get when you make an account with ExpertRec. This key is used to identify
					and power your search...<a href="https://blog.expertrec.com/how-to-get-your-expertrec-site-id/"
												rel="nofollow ugc">(read more)</a>.
				</p>
			</div>
		</div>
		<div class="accordion-item">
			<button id="accordion-button-3" aria-expanded="false">
				<span class="accordion-title">Why is the search bar not appearing?</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					<dd style="display: block;">
				<ol>
					<li>Have you activated the Site Search plugin?</li>
					<li>Is your API Key correct? <a
								href="https://blog.expertrec.com/how-to-get-your-expertrec-site-id/?utm_source=wordpress.org"
								rel="nofollow ugc">Get API key here</a>.
					</li>
					<li>Are you on a custom theme? Some themes may not have a search by default or may interfere with
						the plugin. In this case you may have to add code manually from <a
								href="https://cse.expertrec.com/csedashboard/home/code?fr=wp_plugin" rel="nofollow ugc">here</a>.
					</li>
					<li><a href="https://cloudinfra.freshdesk.com/support/tickets/new" rel="nofollow ugc">Contact
							Support</a></li>
				</ol>
				</dd>
				</p>
			</div>
		</div>
		<div class="accordion-item">
			<button id="accordion-button-4" aria-expanded="false">
				<span class="accordion-title">Does the search bar support live search / ajax search?</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					Yes, the ExpertRec site search plugin is a live search plugin and it will support AJAX search.
				</p>
			</div>
		</div>
		<div class="accordion-item">
			<button id="accordion-button-5" aria-expanded="false">
				<span class="accordion-title">Do I need an account to make a Custom Search Engine</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					Yes, you will need to <a href="https://cse.expertrec.com/?platform=wordpress" rel="nofollow ugc">sign
						up</a> with ExpertRec to get your API key. Once you have this, you will be able to add the
					custom search engine to your website.
				</p>
			</div>
		</div>
		<div class="accordion-item">
			<button id="accordion-button-4" aria-expanded="false">
				<span class="accordion-title">Does it support voice search?</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					Voice search is supported by the ExpertRec site search widget on Chrome browser. Support for Firefox
					is in beta and can be made available on request.
				</p>
			</div>
		</div>
		<div class="accordion-item">
			<button id="accordion-button-4" aria-expanded="false">
				<span class="accordion-title">Is voice search free to use?</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					Voice search is entirely free to use. During the 14-day free trial you can test out the full
					capability of the search engine. After that, you can continue using the voice search along with the
					default WordPress search. It is recommended that you take a paid plan for getting maximum
					performance.
				</p>
			</div>
		</div>
		<div class="accordion-item">
			<button id="accordion-button-4" aria-expanded="false">
				<span class="accordion-title">Can I search multiple websites or multiple subdomains using this plugin?</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					Yes, you can do both. Using this plugin your search bar will give results from both your main domain
					and all subdomains. You can also selectively choose which of your subdomains should be included in
					the search results. If you wish to search a completely separate domain, then this can also be done
					by adding the new domain to the list of URLs to crawl.
				</p>
			</div>
		</div>
		<div class="accordion-item">
			<button id="accordion-button-4" aria-expanded="false">
				<span class="accordion-title">Is there any addtional charges for searching multiple URLs?</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					No, all charges are based on the total number of pages in the search index. There is no limit on the
					number of domains that you can add.
				</p>
			</div>
		</div>
		<div class="accordion-item">
			<button id="accordion-button-4" aria-expanded="false">
				<span class="accordion-title">Didn't found the answer I was looking for.</span>
				<span class="icon" aria-hidden="true"></span>
			</button>
			<div class="accordion-content">
				<p>
					<a href="https://cloudinfra.freshdesk.com/support/tickets/new" target="_blank">Raise a support
						ticket</a> or send us an email to <a
							href="mailto:support@expertrec.com">support@expertrec.com</a>
				</p>
			</div>
		</div>
	</div>
</div>
<div>
	<?php require 'expertrec-page-footer.php'; ?>
</div>
<script>
	const items = document.querySelectorAll('.accordion button');

	function toggleAccordion() {
		const itemToggle = this.getAttribute('aria-expanded');

		for (i = 0; i < items.length; i++) {
			items[i].setAttribute('aria-expanded', 'false');
		}

		if (itemToggle == 'false') {
			this.setAttribute('aria-expanded', 'true');
		}
	}

	items.forEach((item) => item.addEventListener('click', toggleAccordion));
</script>
<div>
	<?php do_action( 'er/debug', 'In Expertrec advanced help' ); ?>
</div>
</body>
</html>


