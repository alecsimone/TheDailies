import React from "react";

export default class SlugTitle extends React.Component {
	constructor(props) {
		super(props);
	}

	render() {
		let titleLink;
		if (this.props.type === "twitch") {
			titleLink = `https://clips.twitch.tv/${this.props.slug}`;
		} else if (this.props.type === "twitter") {
			titleLink = `https://twitter.com/statuses/${this.props.slug}`;
		} else if (this.props.type === "gfycat") {
			titleLink = `https://gfycat.com/${this.props.slug}`;
		} else if (this.props.type === "youtube" || this.props.type === "ytbe") {
			titleLink = `https://www.youtube.com/watch?v=${this.props.slug}`;
		} else if (this.props.type === "gifyourgame") {
			titleLink = `https://gifyourgame.com/${this.props.slug}`;
		}

		let rawTitle = this.props.title.stripSlashes();
		let title = window.htmlEntityFix(rawTitle);

		if (title.length > 140) {
			title = title.substring(0, 137) + "...";
		}
		if (this.props.editable) {
			return <textarea className="editableTitle" type="text" value={title} onChange={(e) => this.props.editTitle(e, this.props.slug)} onKeyPress={(e) => this.props.submitNewTitle(e, this.props.slug)} />
		} else {
			return <a href={titleLink} target="_blank">{title}</a>;
		}
	}
};