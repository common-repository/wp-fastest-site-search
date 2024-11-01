<?php



function expertrec_db_way_search_bar_shortcode() {

	$db_way_search_bar = '<style>
						.er_ecom_search_box_container .er_ecom_input_container {
							width: 100%;
							height: 35px;
							border-width: 0px;
							border-color: black;
							border-style: solid;
							border-top-right-radius: 4px;
							border-bottom-right-radius: 4px;
						}
						.er_ecom_search_box_container .er_ecom_search_button {
							float: right;
							width: 45px;
							height: 100%;
							border: 0;
							border-top-right-radius: 4px;
							border-bottom-right-radius: 4px;
							margin: 0;
							padding: 0 15px;
							color: white;
							background: #3c3c3c;
							cursor: pointer;
						}
						.er_ecom_search_box_container .er_ecom_inputdiv {
							overflow: hidden;
							height: 100%;
						}
						.er_ecom_search_box_container .er_ecom_search_input {
							width: 100%;
							min-height: 33px;
							border: 1px solid #ACACAC;
							border-right: none;
							line-height: 25px;
							font-size: 14px;
							padding: 0 0 0 10px;
							color: black;
							background: white;
						}
						@media screen and (max-width: 520px) {
							.er-dummy-search {
								display: flex;
								justify-content: center;
							}
						}
						</style>
						<div class="er_shortcode">
							<div class="er-dummy-search-box er-search" aria-label="Search Box">
								<er-dummy-search class="er-dummy-search">
									<div class="er_ecom_search_box_container" aria-label="Search Box">
											<div class="er_ecom_input_container">
												<button class="er_ecom_search_button expertrec_search_button">
													<svg style="margin-top: 5px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path fill="#ffffff" d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
												</button>
												<div class="er_ecom_inputdiv">
													<input class="er_ecom_search_input expertrec_search_input_box" placeholder="Search" autocomplete="off">
												</div>
											</div>
									</div>
								</er-dummy-search>
							</div>
						</div>';

	return $db_way_search_bar;
}

function expertrec_crawl_way_search_bar_shortcode() {

	$crawl_way_search_bar = '<style>
                            .er-dummy-search-box {
                                margin: 0px;
                                padding: 0px;
                                width: auto;
                                display: inline-block;
                            }
                            .er-dummy-search-box .er-search-form {
                                display: inline-flex;
                                width: inherit;
                            }
                            .er-dummy-search-box .er_search_input_dummy {
                                height: 30px;
                                padding: 5px;
                                outline: #efefef;
                                font-size: 13px;
                                font-weight: 300;
                                color: #333;
                                border: solid 1px #00205c;
                                border-radius: 0px;
                                width: auto;
                            }
                            .er-dummy-search-box .er_search_input_dummy {
                                box-shadow: none;
                                background: white;
                                box-sizing: border-box;
                            }
                            .er-dummy-search-box .er_search_button_dummy {
                                font-size: 16px;
                                color: white;
                                background: #00205c;
                                border: none;
                                border-radius: 0px;
                                width: 30px;
                                height: 30px;
                                margin: 0px;
                                display: inherit;
                                justify-content: center;
                                align-items: center;
                                cursor: pointer;
                            }
                            .er-dummy-search-box .expertrec_search_button {
                                padding: 7px;
                            }
                            .er-dummy-search-box .expertrec_search_input_box {
                                width:150px;
                            }
                            @media screen and (max-width: 520px)
                            .er-dummy-search {
                                display: flex;
                                justify-content: center;
                            }
                            </style>
                            <div class="er_shortcode">
                                <div class="er-dummy-search-box er-search" aria-label="Search Box">
                                    <er-dummy-search class="er-dummy-search">
                                        <div class="er-dummy-search-box" aria-label="Search Box">
                                            <div class="er-search-form" role="form">
                                                <input type="text" title="Search" tabindex="0" name="" aria-label="search input" role="searchbox" class="er_search_input_dummy expertrec_search_input_box" placeholder="Search" autocomplete="off">
                                                <button type="button" title="Search" tabindex="0" class="er_search_button_dummy expertrec_search_button" aria-label="Search">
                                                    <svg style="padding: 7px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path fill="#ffffff" d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </er-dummy-search>
                                </div>
                            </div>';

	return $crawl_way_search_bar;
}
