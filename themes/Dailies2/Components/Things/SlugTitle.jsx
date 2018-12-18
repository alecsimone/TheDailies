import React from "react";

export default class SlugTitle extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			// editable: this.props.editable ? this.props.editable : false,
			title: this.props.title,
		}
	}

	editTitle(e) {
		this.setState({title: e.target.value});
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
		}

		let rawTitle = this.state.title.stripSlashes();
		let title = window.htmlEntityFix(rawTitle);

		if (title.length > 140) {
			title = title.substring(0, 137) + "...";
		}
		if (this.props.editable) {
			return <textarea className="editableTitle" type="text" value={title} onChange={(e) => this.editTitle(e)} onKeyPress={(e) => this.props.changeTitle(e, this.props.slug)} />
		} else {
			return <a href={titleLink} target="_blank">{title}</a>;
		}
	}
};