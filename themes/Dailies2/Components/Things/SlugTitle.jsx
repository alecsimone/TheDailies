import React from "react";

const SlugTitle = (titleData) => {
	let titleLink;
	if (titleData.type === "twitch") {
		titleLink = `https://clips.twitch.tv/${titleData.slug}`;
	} else if (titleData.type === "twitter") {
		titleLink = `https://twitter.com/statuses/${titleData.slug}`;
	} else if (titleData.type === "gfycat") {
		titleLink = `https://gfycat.com/${titleData.slug}`;
	} else if (titleData.type === "youtube" || titleData.type === "ytbe") {
		titleLink = `https://www.youtube.com/watch?v=${titleData.slug}`;
	}

	let rawTitle = titleData.title.stripSlashes();
	let title = window.htmlEntityFix(rawTitle);

	if (title.length > 140) {
		title = title.substring(0, 137) + "...";
	}

	return <a href={titleLink} target="_blank">{title}</a>;
};

export default SlugTitle;