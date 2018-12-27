import React from "react";

const MetaBox = ({metaData}) => {
	let clipTime = new Date(metaData.age);
	let currentTime = + new Date();
	let timeSince = currentTime - clipTime;
	if (timeSince < 3600000) {
		var timeAgo = Math.floor(timeSince / 1000 / 60);
		var timeAgoUnit = 'minutes';
		if (timeAgo === 1) {var timeAgoUnit = 'minute'};
	} else {
		var timeAgo = Math.floor(timeSince / 1000 / 60 / 60);
		var timeAgoUnit = 'hours';
		if (timeAgo === 1) {var timeAgoUnit = 'hour'};
	}

	let meta = '';
	if (metaData.views) {meta += `${metaData.views} views.`;}
	if (metaData.clipper) {meta += ` Clipped by ${metaData.clipper}`;}
	if (timeAgo) {meta += ` about ${timeAgo} ${timeAgoUnit} ago.`;}

	let vodlink;
	if (metaData.vodlink && metaData.vodlink !== "none") {
		vodlink = <a href={metaData.vodlink} className="vodlink" target="_blank">VOD Link</a>;
	}

	let taxbox;
	// if ((metaData.stars && metaData.stars.length > 0 && metaData.stars[0].name) || (metaData.source && metaData.source.length > 0 && metaData.source[0].name)) {
		let stars = false;
		if (metaData.stars && metaData.stars.length > 0 && metaData.stars[0].name) {
			stars = metaData.stars.map((star) => <a key={`${metaData.slug}-${star.name}`} className="star" href={`${dailiesGlobalData.thisDomain}/stars/${star.slug}`} >{star.name}</a>);
		}
		let source = false;
		if (metaData.source && metaData.source.length > 0 && metaData.source[0].name) {
			source = metaData.source.map((source) => <a key={`${metaData.slug}-${source.name}`} className="source" href={`${dailiesGlobalData.thisDomain}/source/${source.slug}`} >{source.name}</a>);
		}
		let tags = false;
		if (metaData.tags && metaData.tags.length > 0 && metaData.tags[0].name) {
			tags = metaData.tags.map((tag) => <a key={`${metaData.slug}-${tag.name}`} className="tag" href={`${dailiesGlobalData.thisDomain}/tag/${tag.slug}`} >{tag.name}</a>);
		}
		let skills = false;
		if (metaData.skills && metaData.skills.length > 0 && metaData.skills[0].name) {
			skills = metaData.skills.map((skill) => <a key={`${metaData.slug}-${skill.name}`} className="skill" href={`${dailiesGlobalData.thisDomain}/skills/${skill.slug}`} >{skill.name}</a>);
		}
		// taxbox = <div key={`${metaData.slug}-taxbox`} className="taxbox">Stars:{stars}, From:{source}. More:{tags}</div>
	// }

	return <div key={`${metaData.slug}-data`} className="hopefuls-data">{stars ? "Stars:": ""}{stars} {source ? "From:": ""}{source} {tags || skills ? "More:": ""}{tags}{skills} {meta} {vodlink}</div>;
}

export default MetaBox;