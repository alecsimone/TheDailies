import React from "react";

const LoginBox = `
	<div id="wp-social-login" class="">
		<style type="text/css">
			.wp-social-login-connect-with{}.wp-social-login-provider-list{}.wp-social-login-provider-list a{}.wp-social-login-provider-list img{}.wsl_connect_with_provider{}
		</style>
		<div class="wp-social-login-widget">
			<div class="wp-social-login-connect-with">Login now with:</div>
			<div class="wp-social-login-provider-list">
				<a rel="nofollow" href="https://dailies.gg/wp-login.php?action=wordpress_social_authenticate&amp;mode=login&amp;provider=Facebook&amp;redirect_to=https%3A%2F%2Fdailies.gg%2F" title="Connect with Facebook" class="wp-social-login-provider wp-social-login-provider-facebook" data-provider="Facebook">
					Facebook
				</a>
				<a rel="nofollow" href="https://dailies.gg/wp-login.php?action=wordpress_social_authenticate&amp;mode=login&amp;provider=Google&amp;redirect_to=https%3A%2F%2Fdailies.gg%2F" title="Connect with Google" class="wp-social-login-provider wp-social-login-provider-google" data-provider="Google">
					Google
				</a>
				<a rel="nofollow" href="https://dailies.gg/wp-login.php?action=wordpress_social_authenticate&amp;mode=login&amp;provider=Twitter&amp;redirect_to=https%3A%2F%2Fdailies.gg%2F" title="Connect with Twitter" class="wp-social-login-provider wp-social-login-provider-twitter" data-provider="Twitter">
					Twitter
				</a>
				<a rel="nofollow" href="https://dailies.gg/wp-login.php?action=wordpress_social_authenticate&amp;mode=login&amp;provider=Steam&amp;redirect_to=https%3A%2F%2Fdailies.gg%2F" title="Connect with Steam" class="wp-social-login-provider wp-social-login-provider-steam" data-provider="Steam">
					Steam
				</a>
				<a rel="nofollow" href="https://dailies.gg/wp-login.php?action=wordpress_social_authenticate&amp;mode=login&amp;provider=TwitchTV&amp;redirect_to=https%3A%2F%2Fdailies.gg%2F" title="Connect with Twitch.tv" class="wp-social-login-provider wp-social-login-provider-twitchtv" data-provider="TwitchTV">
					Twitch.tv
				</a>
			</div>
			<div class="wp-social-login-widget-clearing"></div>
		</div>
	</div>
`

const ShowLoginBox = () => {
	var AddProspectBox = document.createElement("section");
	AddProspectBox.id = 'loggedOutProspectForm'
	document.body.appendChild(AddProspectBox);
	var lightboxOverlayElement = document.createElement("div");
	lightboxOverlayElement.id = 'lightboxOverlay';
	document.body.appendChild(lightboxOverlayElement);
	let loggedOutProspectForm = document.getElementById('loggedOutProspectForm');
	loggedOutProspectForm.innerHTML = LoginBox;
}

export default ShowLoginBox;