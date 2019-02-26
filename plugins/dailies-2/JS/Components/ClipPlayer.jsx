import React from "react";

export default class ClipPlayer extends React.Component{
	componentDidUpdate() {
		var tweetContainer = jQuery(`#twitterVideo${this.props.slug}`);
		if (tweetContainer.length) {
			var tweetWidget = jQuery("[id^='twitter-widget']");
			if (tweetWidget.length) {
				var embeddedTweet = tweetWidget.attr("data-tweet-id");
				if (embeddedTweet) {
					if (embeddedTweet != this.props.slug) {
						tweetWidget.remove();
						twttr.widgets.createTweet(
			  				this.props.slug,
			  				document.getElementById(`twitterVideo${this.props.slug}`),
			  				{
			  					theme: "dark",
			  					conversation: "none",
			  					align: "center",
			  					dnt: true,
			  				}
						);
					}
				}
			} else {
				twttr.widgets.createTweet(
	  				this.props.slug,
	  				document.getElementById(`twitterVideo${this.props.slug}`),
	  				{
	  					theme: "dark",
	  					conversation: "none",
	  					align: "center",
	  					dnt: true,
	  				}
				);
			}
			if (tweetWidget.length > 1) {
				for (var i = 1; i < tweetWidget.length; i++) {
					tweetWidget[i].remove();
				}
			}
		}




		// var tweetContainer = jQuery(`#twitterVideo${this.props.slug}`);
		// if (tweetContainer.length && !jQuery('#twitter-widget-0').length) {
		// 	console.log("conditional 1");
		// 	twttr.widgets.createTweet(
  // 				this.props.slug,
  // 				document.getElementById(`twitterVideo${this.props.slug}`)
		// 	);
		// } else if (tweetContainer.length && jQuery('#twitter-widget-0').length && jQuery('#twitter-widget-0').attr("data-tweet-id") != this.props.slug) {
		// 	console.log("conditional 2");
		// 	twttr.widgets.createTweet(
  // 				this.props.slug,
  // 				document.getElementById(`twitterVideo${this.props.slug}`)
		// 	);
		// }

	}

	render() {
		if (this.props.width) {
			let seedlingInfoWidth = this.props.width;
			if (seedlingInfoWidth > 1920) {seedlingInfoWidth = 1920};
			let embedHeight = seedlingInfoWidth * 9 / 16;
		}
		let autoplay;
		if (this.props.autoplay === undefined) {
			autoplay = true;
		} else {
			autoplay = this.props.autoplay;
		}
		let iframeSrc;
		if (this.props.type === "twitch" || this.props.type === undefined) {
			iframeSrc = `https://clips.twitch.tv/embed?clip=${this.props.slug}&autoplay=${autoplay}`;
		}
		if (this.props.type === "gfycat") {
			iframeSrc = `https://gfycat.com/ifr/${this.props.slug}?autoplay=${autoplay ? 1 : 0}&hd=1`;
		}
		if (this.props.type === "youtube" || this.props.type === "ytbe") {
			iframeSrc = `https://www.youtube.com/embed/${this.props.slug}?autoplay=${autoplay ? '1' : 0}&loop=1&playlist=${this.props.slug}`
		}
		if (this.props.type === "twitter") {
			if (this.props.vodlink !== "none") {
				let isAutoPlay;
				autoplay ? isAutoPlay = {autoPlay: true} : isAutoPlay = {};
				return (
					<div className="embed-container">
						<video controls {...isAutoPlay} loop muted src={this.props.vodlink} type="video/mp4" />
					</div>
				)
				// onClick={(e) => {e.target.paused ? e.target.play() : e.target.pause()} }
			} else {
				return (
					<div className="embed-container">
						<div id={`twitterVideo${this.props.slug}`} className="twitterVideoContainer"></div>
					</div>
				)
			}
		}
		if (this.props.type === "gifyourgame") {
			let isAutoPlay;
			autoplay ? isAutoPlay = {autoPlay: true} : isAutoPlay = {};
			return (
				<div className="embed-container">
					<video controls {...isAutoPlay} loop muted src={this.props.vodlink} type="video/mp4" />
				</div>
			)
			//onClick={(e) => {e.target.paused ? e.target.play() : e.target.pause()} }
		}
		return(
			<div className="embed-container">
				<iframe src={iframeSrc} frameBorder='0' scrolling='no' allowFullScreen='true'></iframe>
			</div>
		);
	}
}