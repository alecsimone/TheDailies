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

	return <div className="hopefuls-data">{meta} {vodlink}</div>;
}

export default MetaBox;