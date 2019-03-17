import React from "react";
import ReactDOM from 'react-dom';
import {CSSTransition, TransitionGroup} from 'react-transition-group';
import ClipPlayer from './ClipPlayer.jsx';
import Comments from './Comments.jsx';
import Thing from './Things/Thing.jsx';
import {privateData} from '../Scripts/privateData.jsx';

export default class Weed extends React.Component{
	constructor() {
		super();
		console.groupCollapsed("constructor");
		jQuery.each(weedData.clips, function(slug, slugObj) {
			if (slugObj.score === undefined) {
				slugObj.score = 0;
			}
			if (slugObj.nuked === undefined) {
				slugObj.nuked = 0;
			}
			if (slugObj.nuked == 2) {
				delete weedData.clips[slug];
			}
		});

		let youJudged = 0;
		jQuery.each(weedData.seenSlugs, function(index, seenSlugObject) {
			if (Object.keys(weedData.clips).includes(seenSlugObject.slug)) {
				delete weedData.clips[seenSlugObject.slug];
				youJudged++;
			}
		});


		this.state = {
			clips: weedData.clips,
			seenSlugs: weedData.seenSlugs,
			comments: [],
			commentsLoading: true,
			newClip: true,
			lastVoteDirection: null,
			totalClips: Object.keys(weedData.clips).length + youJudged,
			youJudged,
			voters: "loading",
			nukers: [],
		};

		if (this.state.seenSlugs.length === undefined) {
			console.log(this.state.seenSlugs.length);
			console.log("Changing seenSlugs from an object to an array");
			let seenSlugsArray = [];
			let seenSlugsObject = this.state.seenSlugs;
			let seenKeys = Object.keys(this.state.seenSlugs);
			console.log(seenKeys.length);
			seenKeys.forEach(function(key) {
				seenSlugsArray.push(seenSlugsObject[key]);
			});
			this.state.seenSlugs = seenSlugsArray;
		}

		this.sortClips = this.sortClips.bind(this);
		this.judgeClip = this.judgeClip.bind(this);
		this.nukeButtonHandler = this.nukeButtonHandler.bind(this);
		this.postComment = this.postComment.bind(this);
		this.yeaComment = this.yeaComment.bind(this);
		this.delComment = this.delComment.bind(this);
		this.sortByVOD = this.sortByVOD.bind(this);
		this.blacklistVod = this.blacklistVod.bind(this);
		
		this.state.clipsArray = this.sortByGravitas(Object.keys(weedData.clips));

		console.groupEnd("constructor");
	}

	turnVodlinkIntoMomentObject(vodlink) {
		if (vodlink === "none") {
			return false;
		}
		let vodIDIndex = vodlink.indexOf('/videos/') + 8;
		let vodTimeIndex = vodlink.indexOf('?t=') + 3;
		let vodID = vodlink.substring(vodIDIndex, vodTimeIndex - 3);

		let timestamp = vodlink.substring(vodTimeIndex, vodlink.length);
		let timestampHourIndex = timestamp.indexOf('h');
		if (timestampHourIndex > -1) {
			var timestampHours = Number(timestamp.substring(0, timestampHourIndex));
		} else {
			var timestampHours = 0;
		}
		let timestampMinuteIndex = timestamp.indexOf('m');
		if (timestampMinuteIndex > -1) {
			if (timestampHourIndex > -1) {
				var timestampMinutes = Number(timestamp.substring(timestampHourIndex + 1, timestampMinuteIndex));
			} else {
				var timestampMinutes = Number(timestamp.substring(0, timestampMinuteIndex));
			}
		} else {
			var timestampMinutes = 0;
		}
		let timestampSecondIndex = timestamp.indexOf('s');
		if (timestampSecondIndex > -1) {
			if (timestampMinuteIndex > -1) {
				var timestampSeconds = Number(timestamp.substring(timestampMinuteIndex + 1, timestampSecondIndex));
			} else if (timestampHourIndex > -1) {
				var timestampSeconds = Number(timestamp.substring(timestampHourIndex + 1, timestampSecondIndex));
			}
		} else {
			var timestampSeconds = 0;
		}
		let vodtime = timestampSeconds + 60 * timestampMinutes + 60 * 60 * timestampHours;
		return {
			vodID,
			vodtime,
		};
	}

	sortClips(clipsArray) {
		let clipsData = this.state.clips;
		clipsArray.forEach( (slug, index) => {
			if (clipsData[slug].nuked == 2) {
				console.log(`${slug} has been nuked, but is still showing up`);
				clipsArray.splice(index, 1);
			}
		});
		clipsArray.sort(this.sortByVOD);
		return clipsArray;
	}

	sortByGravitas(clipsArray) {
		let clipsData = this.state.clips;
		let clipVods = {}; //clipVods will have vodIDs as keys, and an array of slugs with that vodID as their properties
		let looseClips = {}; //Will be just like weedData.clips, an object of slugs attached to their data
		clipsArray.forEach( (slug, index) => {
			if (clipsData[slug].nuked == 2) {
				console.log(`${slug} has been nuked, but is still showing up`);
				clipsArray.splice(index, 1);
			}
			if (clipsData[slug].vodlink !== "none" && clipsData[slug].vodlink.indexOf("twimg.com") == -1 && clipsData[slug].vodlink.indexOf("gfycat") == -1 && clipsData[slug].vodlink.indexOf("gifyourgame") == -1) {
				let currentMoment = this.turnVodlinkIntoMomentObject(clipsData[slug].vodlink);
				if (clipVods[currentMoment['vodID']]) {
					clipVods[currentMoment['vodID']].push(slug);
				} else {
					clipVods[currentMoment['vodID']] = [slug];
				}
			} else {
				looseClips[slug] = clipsData[slug];
			}
		});

		let clipViewsObject = {};
		let clipScoreObject = {};
		let clipVotesObject = {};

		Object.keys(looseClips).forEach( (slug) => {
			clipViewsObject[slug] = Number(clipsData[slug].views);
			clipScoreObject[slug] = Number(clipsData[slug].score);
			clipVotesObject[slug] = Number(clipsData[slug].votecount);
		});

		Object.keys(clipVods).forEach( (vodID) => {
			if (clipVods[vodID].length === 1) {
				let thatSlugsData = clipsData[clipVods[vodID][0]];
				looseClips[thatSlugsData.slug] = thatSlugsData;
				clipViewsObject[thatSlugsData.slug] = Number(thatSlugsData.views);
				clipScoreObject[thatSlugsData.slug] = Number(thatSlugsData.score);
				clipVotesObject[thatSlugsData.slug] = Number(thatSlugsData.votecount);
				delete clipVods[vodID];
			} else {
				let thisVodsViews = 0;
				let thisVodsScore = 0;
				let thisVodsLowestVotes;
				clipVods[vodID].forEach( (slug) => {
					if (thisVodsLowestVotes === undefined || thisVodsLowestVotes > Number(clipsData[slug].votecount)) {
						thisVodsLowestVotes = Number(clipsData[slug].votecount);
					}
					thisVodsViews += Number(clipsData[slug].views);
					if (Number(clipsData[slug].score) > 0) {
						thisVodsScore += Number(clipsData[slug].score);
					}
				});
				clipViewsObject[vodID] = thisVodsViews;
				clipScoreObject[vodID] = thisVodsScore;
				clipVotesObject[vodID] = thisVodsLowestVotes;
			}
		});

		// let sortedClipViewsArray = Object.keys(clipViewsObject).sort( (a, b) => {
		// 	if (clipViewsObject[a] == 0 && clipViewsObject[b] != 0) {
		// 		return -1;
		// 	}
		// 	if (clipViewsObject[b] == 0 && clipViewsObject[a] != 0) {
		// 		return 1;
		// 	}
		// 	return clipViewsObject[b] - clipViewsObject[a];
		// });

		let sortedClipVotesArray = Object.keys(clipVotesObject).sort( (a, b) => {
			if (clipVotesObject[a] === clipVotesObject[b]) {
				// if (clipViewsObject[a] == 0 && clipViewsObject[b] != 0) {
				// 	return -1;
				// }
				// if (clipViewsObject[b] == 0 && clipViewsObject[a] != 0) {
				// 	return 1;
				// }
				if (clipScoreObject[a] === clipScoreObject[b]) {
					if (clipViewsObject[a] == 0 && clipViewsObject[b] != 0) {
						return -1;
					}
					if (clipViewsObject[b] == 0 && clipViewsObject[a] != 0) {
						return 1;
					}
					return clipViewsObject[b] - clipViewsObject[a];
				}
				return clipScoreObject[b] - clipScoreObject[a];
			}
			return clipVotesObject[a] - clipVotesObject[b];
		});

		let sortedClipsArray = [];

		sortedClipVotesArray.forEach( (identifier) => {
		// sortedClipViewsArray.forEach( (identifier) => {
			if (Number(identifier) !== NaN && Number(identifier) < 1000000000000000000) {
				clipVods[identifier].sort( (a, b) => {
					let momentA = this.turnVodlinkIntoMomentObject(clipsData[a].vodlink);
					let momentB = this.turnVodlinkIntoMomentObject(clipsData[b].vodlink);
					return momentA.vodtime - momentB.vodtime;
				});
				clipVods[identifier].forEach((slug) => {
					sortedClipsArray.push(slug)
				});
			} else {
				sortedClipsArray.push(identifier);
			}
		});

		sortedClipsArray.sort( (a, b) => {
			if (dailiesGlobalData.userData.userRole === "administrator" || dailiesGlobalData.userData.userRole === "editor") {
				return Number(clipsData[b].nuked) - Number(clipsData[a].nuked);
			} else {
				return Number(clipsData[a].nuked) - Number(clipsData[b].nuked);
			}
		});

		return sortedClipsArray;
	}

	sortByVOD(a, b) {
		let dataA = {
			score: Number(this.state.clips[a].score),
			views: Number(this.state.clips[a].views),
			timestamp: new Date(this.state.clips[a].age).getTime(),
			vodlink: this.turnVodlinkIntoMomentObject(this.state.clips[a].vodlink),
			votecount: Number(this.state.clips[a].votecount),
		};
		if (dataA.vodlink.vodID) {
			dataA.vodlink.vodID = Number(dataA.vodlink.vodID);
		} else {
			return 1;
		}

		let dataB = {
			score: Number(this.state.clips[b].score),
			views: Number(this.state.clips[b].views),
			timestamp: new Date(this.state.clips[b].age).getTime(),
			vodlink: this.turnVodlinkIntoMomentObject(this.state.clips[b].vodlink),
			votecount: Number(this.state.clips[b].votecount),
		};
		if (dataB.vodlink.vodID) {
			dataB.vodlink.vodID = Number(dataB.vodlink.vodID);
		} else {
			return -1;
		}

		if (dataA.votecount !== dataB.votecount) {
			if (dataA.votecount === 0) {
				return -1;
			}
			if (dataB.votecount === 0) {
				return 1;
			}
		}

		if (dataA.vodlink.vodID !== dataB.vodlink.vodID) {
			return dataA.vodlink.vodID - dataB.vodlink.vodID;
		} else {
			if (dataA.vodlink.vodtime !== dataB.vodlink.vodtime) {
				return dataA.vodlink.vodtime - dataB.vodlink.vodtime;
			} else {
				if (dataA.vodlink.score !== dataB.vodlink.score) {
					return dataB.vodlink.score - dataA.vodlink.score;
				} else {
					if (dataA.vodlink.views !== dataB.vodlink.views) {
						return dataB.vodlink.views - dataA.vodlink.views;
					} else {
						if (dataA.vodlink.timestamp !== dataB.vodlink.timestamp) {
							return dataB.vodlink.timestamp - dataA.vodlink.timestamp;
						}
					}
				}
			}
		}
		return 0;

	}

	getSortScore(slug) {
		let score = Number(this.state.clips[slug].score);
		let views = Number(this.state.clips[slug].views);
		
		let timestamp = new Date(this.state.clips[slug].age).getTime();
		let now = new Date().getTime();
		let age = now - timestamp;
		var queryHours = parseInt(weedData.queryHours, 10);


		var priorityStreamList = Object.keys(weedData.streamList);
		var upperCaseStreamList = priorityStreamList.map(function(name) {
			return name.toUpperCase();
		});
		if (this.state.clips[slug].source) {
			var streamPriority = upperCaseStreamList.indexOf(this.state.clips[slug].source.toUpperCase());
			var upperCaseGoodStreams = weedData.goodStreams.map(function(name) {
				return name.toUpperCase();
			});
			var isGoodStream = upperCaseGoodStreams.indexOf(this.state.clips[slug].source.toUpperCase());
		} else {
			var streamPriority = -1;
			var isGoodStream = -1;
		}
		

		let sortScore = score + (Math.log10(views) * 5) - (age / 1000 / 60 / 60 / 10);
		if (views < 3) {sortScore = sortScore - 100;}
		if (streamPriority > -1) {sortScore = sortScore + 10;}
		if (isGoodStream > -1) {sortScore = sortScore + 5;}
		if (Number(this.state.clips[slug].votecount) == 0) {sortScore = sortScore + 2000;}
		if (Number(this.state.clips[slug].votecount) == 1) {sortScore = sortScore + 1000;}
		if (Number(this.state.clips[slug].votecount) == 2) {sortScore = sortScore + 500;}
		if (Number(this.state.clips[slug].nuked) == 1) {sortScore = sortScore - 10000;}
		if (age / 1000 / 60 / 60 - 6 > queryHours) {sortScore = sortScore - 100;}

		return sortScore;
	}

	judgeClip(e) {
		let lastVoteDirection;
		e.currentTarget.classList.contains("yeaButton") ? lastVoteDirection = "up" : lastVoteDirection = "down";
		console.log(`You ${lastVoteDirection}voted ${this.firstSlug}`);
		this.setState({
			newClip: false,
			commentsLoading: true,
			lastVoteDirection,
			// voters: "loading",
			youJudged: this.state.youJudged + 1,
		});
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				slug: this.firstSlug,
				judgment: lastVoteDirection,
				action: 'judge_slug',
			},
			error: function(one, two, three) {
				console.log(one);
				console.log(two);
				console.log(three);
			},
			success: (data) => {
				let seenSlugs = this.state.seenSlugs;
				let clipsArray = this.state.clipsArray;
				seenSlugs.push({slug: this.firstSlug});
				clipsArray.shift();
				let clips = this.state.clips;
				clips[this.firstSlug].votecount = clips[this.firstSlug].votecount + 1;
				this.setState({
					newClip: true,
					seenSlugs,
					clipsArray,
					clips,
				});
			}
		});
		// let dupeSlugs = this.findAllDupes(this.firstSlug);
		// if (dupeSlugs) {
		// 	dupeSlugs.forEach(function(slugToNuke) {
		// 		console.log(`Nuking ${slugToNuke} because it's the same moment as the clip you just judged`);
		// 		boundThis.nukeSlug(slugToNuke);
		// 	});
		// }
		// jQuery.ajax({
		// 	type: "POST",
		// 	url: dailiesGlobalData.ajaxurl,
		// 	dataType: 'json',
		// 	data: {
		// 		slug,
		// 		vodlink: currentState.clips[this.firstSlug].vodlink,
		// 		judgment: e.currentTarget.id,
		// 		action: 'judge_slug',
		// 	},
		// 	error: function(one, two, three) {
		// 		console.log(one);
		// 		console.log(two);
		// 		console.log(three);
		// 	},
		// 	success: (data) => {
		// 		console.log(data);
		// 		if (typeof data === "string" && data.startsWith("Unknown Clip")) {
		// 			console.log(`${slug} was not found in the clip database`);
		// 			currentState.seenSlugs.push({slug});
		// 			delete currentState.clips[slug];
		// 		} else if (data != "Dummy just passed") {
		// 			let vodlink = data.vodlink;
		// 			if (vodlink !== undefined) {
		// 				currentState.seenMoments.push(boundThis.turnVodlinkIntoMomentObject(vodlink))
		// 			}
		// 			currentState.clips[data.slug].score = data.score;
		// 			currentState.clips[data.slug].votecount = data.votecount;
		// 			currentState.seenSlugs.push(data);
		// 			currentState.clipsArray.shift();
		// 			currentState.youJudged++;
		// 			console.log(`increasing youjudged because you judged ${data.slug}`);
		// 			let dupeSlugs = boundThis.findAllDupes(data.slug);
		// 			if (dupeSlugs) {
		// 				dupeSlugs.forEach(function(slugToNuke) {
		// 					console.log(`Nuking ${slugToNuke} because it's the same moment as the clip you just judged`);
		// 					boundThis.nukeSlug(slugToNuke);
		// 				});
		// 			} 
		// 		} else {
		// 			currentState.seenSlugs.push({slug: boundThis.firstSlug});
		// 			delete currentState.clips[slug];
		// 		}
		// 		currentState.commentsLoading = true;
		// 		currentState.comments = [];
		// 		currentState.newClip = true;
		// 		currentState.lastVoteDirection = lastVoteDirection;
		// 		boundThis.setState(currentState);
		// 	}
		// });
	}

	findAllDupes(slug) {
		let slugData = this.state.clips[slug];
		if (slugData === undefined || slugData.vodlink === 'none') {
			return [];
		}
		let vodlink = slugData.vodlink;
		let vodMoment = this.turnVodlinkIntoMomentObject(vodlink);
		let dupeSlugs = [];
		jQuery.each(this.state.clips, (index, clipData) => {
			if (clipData.slug === slug) {return true;}
			let currentVodMoment = this.turnVodlinkIntoMomentObject(clipData.vodlink);
			if (!currentVodMoment) {return true;}
			if (vodMoment.vodID != currentVodMoment.vodID) {return true;}
			if (vodMoment.vodtime + 25 >= currentVodMoment.vodtime && vodMoment.vodtime - 25 <= currentVodMoment.vodtime) {
				dupeSlugs.push(clipData.slug);
			}
		});
		return dupeSlugs;
	}

	removeSeenSlugs(clipList) {
		let seenSlugs = this.state.seenSlugs;
		jQuery.each(seenSlugs, function(index, seenSlugObject) {
			if (clipList.includes(seenSlugObject.slug)) {
				let seenSlugIndex = clipList.indexOf(seenSlugObject.slug);
				clipList.splice(seenSlugIndex, 1);
				// console.log(`Removing ${seenSlugObject.slug} because you've already seen it`);
			}
		});
		return clipList;
	}

	checkMomentFreshness(momentObject) {
		let vodID = momentObject.vodID;
		let vodtime = momentObject.vodtime;
		let isFresh = true;
		jQuery.each(this.state.seenMoments, function(index, moment) {
			if (vodID == moment.vodID) {
				if (vodtime + 25 >= moment.vodtime && vodtime - 25 <= moment.vodtime) {
					isFresh = false;
				}
			}
		})
		return isFresh;
	}
	nukeButtonHandler() {
		if (dailiesGlobalData.userData.userRole !== "administrator" && dailiesGlobalData.userData.userRole !== "editor") {
			if (!window.confirm("This is the nuke button. Only hit this button on clips that aren't remotely interesting or relevant in any way. They'll get moved to the back of the queue for everyone else until a mod deletes them. Do NOT nuke anything that there's any chance someone, anyone, might like. Do you want to nuke this clip?")) {
				return;
			}
		}
		this.setState({
			newClip: false,
			lastVoteDirection: "down",
		});
		console.log(`You hit the nuke button on ${this.firstSlug}`);
		// let dupeSlugs = this.findAllDupes(this.firstSlug);
		let boundThis = this;
		this.nukeSlug(this.firstSlug);
		// dupeSlugs.forEach(function(slugToNuke) {
		// 	console.log(`Nuking ${slugToNuke} because it's the same moment as the clip you just judged`);
		// 	boundThis.nukeSlug(slugToNuke);
		// });
	}

	nukeSlug(slug) {
		if (!weedData.clips[slug]) {
			return;
		}
		// weedData.clips[slug].nuked = 1;
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				slug,
				action: 'nuke_slug',
			},
			error: function(one, two, three) {
				console.log(one);
				console.log(two);
				console.log(three);
			},
			success: (data) => {
				console.log(`you've nuked ${data}! And we'll be increasing youjudged because of it`);
				let clips = this.state.clips;
				if (dailiesGlobalData.userData.userRole === "administrator") {
					clips[data].nuked = 2;
				} else if (dailiesGlobalData.userData.userRole === "editor") {
					clips[data].nuked = Number(clips[data].nuked) + 1;
				} else if (Number(dailiesGlobalData.userData.rep) >= 5 && clips[data].nuked < 2) {
					clips[data].nuked = 1;
				}
				let clipsArray = this.state.clipsArray;
				clipsArray.splice(0,1);
				this.setState({
					clips,
					clipsArray,
					newClip: true,
					totalClips: this.state.totalClips - 1,
				});
			},
		});
	}

	unNukeSlug(slug) {
		console.log(slug);
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				slug,
				action: 'un_nuke_slug',
			},
			error: function(one, two, three) {
				console.log(one);
				console.log(two);
				console.log(three);
			},
			success: (data) => {
				if (data == true) {
					this.setState({nukers: []});
				}
			}
		});
	}

	postComment(commentObject) {
		let currentState = this.state;
		currentState.commentsLoading = true;
		this.setState(currentState);
		// let randomID = Math.round(Math.random() * 100);
		let boundThis = this;
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				slug: this.firstSlug,
				commentObject,
				action: 'post_comment',
			},
			error: function(one, two, three) {
				console.log(one);
				console.log(two);
				console.log(three);
			},
			success: function(data) {
				// jQuery.each(currentState.comments, function(index,commentData) {
				// 	if (commentData.id == randomID) {
				// 		currentState.comments[index].id = data;
				// 	}
				// });
				// this.setState(currentState);
				let commentData = {
					comment: commentObject.comment,
					commenter: dailiesGlobalData.userData.userName,
					pic: dailiesGlobalData.userData.userPic,
					id: data,
					replytoid: commentObject.replytoid,
					slug: this.firstSlug,
					score: 0,
					time: Date.now(),
				}
				currentState.comments.push(commentData);
				currentState.commentsLoading = false;
				boundThis.setState(currentState);
			}
		});
	}

	yeaComment(commentID) {
		let currentState = this.state;
		jQuery.each(currentState.comments, function(index, data) {
			if (data.id == commentID) {
				currentState.comments[index].score = Number(data.score) + 1;
			}
		})
		this.setState(currentState);
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				commentID,
				action: 'yea_comment',
			},
			error: function(one, two, three) {
				console.log(one);
				console.log(two);
				console.log(three);
			},
			success: function(data) {
				console.log(data);			
			}
		});
	}

	delComment(commentID) {
		let currentState = this.state;
		jQuery.each(currentState.comments, function(index, commentData) {
			if (commentData === undefined) {return true;}
			if (commentID == commentData.id) {
				delete currentState.comments[index];
			}
		});
		this.setState(currentState);
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				commentID,
				action: 'del_comment',
			},
			error: function(one, two, three) {
				console.log(one);
				console.log(two);
				console.log(three);
			},
			success: function(data) {
				console.log(data);			
			}
		});
	}

	blacklistVod(e, slug) {
		e.target.checked = false;
		if (this.state.clips[slug].type !== "twitch") {
			window.alert("that's not a twitch clip, idiot.");
			return;
		}
		if (this.state.clips[slug].vodlink === "none") {
			window.alert("That clip doesn't have a vodlink");
			return;
		}
		const vodlink = this.state.clips[slug].vodlink;
		const {vodID} = this.turnVodlinkIntoMomentObject(vodlink);
		const rawVodlink = `twitch.tv/videos/${vodID}`

		if (window.confirm(`Do you want to blacklist ${rawVodlink}?`)) {
			jQuery.ajax({
				type: "POST",
				url: dailiesGlobalData.ajaxurl,
				dataType: 'json',
				data: {
					vodID,
					action: 'blacklist_vod',
				},
				error: function(one, two, three) {
					console.log(one);
					console.log(two);
					console.log(three);
				},
				success: (data) => {
					let clipsArray = this.state.clipsArray;
					let totalClips = this.state.totalClips;
					let clips = this.state.clips;
					let indicesToSplice = [];
					clipsArray.forEach((slug, index) => {	
						let thisSlugsVodlink = this.state.clips[slug].vodlink;
						if (thisSlugsVodlink.indexOf(rawVodlink) !== -1) {
							indicesToSplice.unshift(index);
							totalClips--;
							clips[slug].nuked = 1;
						}
					});
					indicesToSplice.forEach((index) => {clipsArray.splice(index, 1)});
					this.setState({
						clips,
						clipsArray,
						totalClips,
					});
				}
			});
		}
	}

	componentDidMount() {
		// this.getComments();
		this.getVotes();
		this.getNukes();
	}

	componentDidUpdate(prevProps, prevState) {
		// if (this.state.voters === "loading") {
		if (prevState.newClip === false) {
			this.getVotes();
			this.getNukes();
		}
	}

	getComments() {
		let queryURL = `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/clipcomments/slug=${this.firstSlug}`
		let currentState = this.state;
		let boundThis = this;
		jQuery.get({
			url: queryURL,
			dataType: 'json',
			success: function(data) {
				currentState.comments = data;
				currentState.commentsLoading = false;
				boundThis.setState({
					comments: data,
					commentsLoading: false,
				});
			}
		});
	}

	getVotes() {
		let queryURL = `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/clipvoters/slug=${this.firstSlug}`
		jQuery.get({
			url: queryURL,
			dataType: 'json',
			success: (data) => {
				this.setState({
					voters: data,
					// newClip: true,
				});
			}
		});
	}

	getNukes() {
		let queryURL = `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/clipnukers/slug=${this.firstSlug}`
		jQuery.get({
			url: queryURL,
			dataType: 'json',
			success: (data) => {
				this.setState({
					nukers: data,
					// newClip: true,
				});
			}
		});
	}

	render() {
		console.groupCollapsed("render");
		// console.log(this.state.clipsArray);
		console.log(`Rendering ${this.state.clipsArray[0]}`);
		// let slugsArray = Object.keys(this.state.clips);
		// let sortedClips = this.sortClips(slugsArray);
		// sortedClips = this.removeSeenSlugs(sortedClips);
		if (this.state.clipsArray.length === 0) {
			return(
				<section id="weeder" className="weederVictory">
					<div id="Victory">You won!</div>
				</section>
			)
		}
		this.firstSlug = this.state.clipsArray[0];
		let firstSlugData = this.state.clips[this.firstSlug];
		firstSlugData.voters = this.state.voters;

		let unjudgedClipCounter = 0;
		let yourUndjudgedClips = 0;
		jQuery.each(this.state.clips, function(index, clipData) {
			if (clipData.votecount == 0 && clipData.nuked == 0) {unjudgedClipCounter++;}
		});

		let nukersDisplay;
		if (this.state.nukers.length > 0) {
			let nukers = this.state.nukers.map( (nukerData) => {
				return <img key={nukerData.hash} className="nukerBubble" src={nukerData.picture} title={nukerData.name} onError={(e) => window.imageError(e, 'twitchVoter')} />
			});
			nukers.unshift(<h5 className="nukersHeader">Nuked by</h5>);
			if (dailiesGlobalData.userData.userRole === "administrator" || dailiesGlobalData.userData.userRole === "editor") {
				nukers.push(<h5 className="nukersHeader unNuke" onClick={() => this.unNukeSlug(this.firstSlug)} >(un-nuke)</h5>);
			}
			nukersDisplay = <div className="nukers">{nukers}</div>
		}

		let width = jQuery(window).width() - 10;
		let windowHeight = jQuery(window).height();
		let menuLinksDivHeight = jQuery("#menu-links").height();
		let menuLinksMarginBottomString = jQuery("#menu-links").css("marginBottom");
		let menuLinksMarginBottomStringLength = menuLinksMarginBottomString.length;
		let menuLinksMarginBottom = Number(menuLinksMarginBottomString.substring(0, menuLinksMarginBottomStringLength - 2));
		let menuLinksHeight = menuLinksDivHeight + menuLinksMarginBottom;
		let height = windowHeight - menuLinksHeight;
		let playerHeight = height - 150;
		let playerWidth = playerHeight * 16 / 9;
		if (playerWidth + 250 < width) {
			var orientation = "Landscape";
		} else {
			var orientation = "Portrait";
			playerWidth = width;
		}
		var playerStyle = {
			maxWidth: playerWidth,
		}
		let usWidth = 100 * (this.state.totalClips - Number(unjudgedClipCounter)) / this.state.totalClips;
		var usStyle = {
			width: usWidth + '%',
		}
		let youWidth = 100 * (this.state.youJudged) / this.state.totalClips;
		var youStyle = {
			width: youWidth + '%',
		}

		let admin = {};
		if (dailiesGlobalData.userData.userRole === "administrator") {
			admin.cut = this.nukeButtonHandler;
			admin.toggle = this.blacklistVod;
		} else if (dailiesGlobalData.userData.userRole === "editor" || Number(dailiesGlobalData.userData.userRep) >= 5) {
			admin.cut = this.nukeButtonHandler;
			if (Array.isArray(this.state.voters)) {
				this.state.voters.forEach( (vote) => {
					if (Number(vote.weight) > 0) {
						delete admin.cut;
					}
				});
			}
		}

		console.groupEnd("render");
		return(
			<section id="weeder" className={"weeder" + orientation}>
				<CSSTransition
					in={true}
					timeout={500}
					appear={true}
					classNames={{
						appear: "slide-down",
						appearActive: "slide-down-active"
					}}
				>
					<div id="clipsLeftCounter">
						<div className="progressBackground">
							<div className="progressText">Us: {this.state.totalClips - Number(unjudgedClipCounter)} / {this.state.totalClips} Clips</div>
							<CSSTransition
								in={true}
								timeout={750}
								appear={true}
								classNames={{
									appear: "barGrow-appear",
									appearActive: "barGrow-appear-active"
								}}
							>
								<div id="usProgress" className="progressBar" style={usStyle} ></div>
							</CSSTransition>
						</div>
						<div className="progressBackground">
							<div className="progressText">You: {this.state.youJudged} / {this.state.totalClips} Clips</div>
							<CSSTransition
								in={true}
								timeout={750}
								appear={true}
								classNames={{
									appear: "barGrow-appear",
									appearActive: "barGrow-appear-active"
								}}
							>
								<div id="youProgress" className="progressBar" style={youStyle} ></div>
							</CSSTransition>
						</div>
					</div>
				</CSSTransition>
				<CSSTransition
					in={this.state.newClip}
					timeout={500}
					appear={true}
					classNames={{
						appear: "scale-up",
						appearActive: "scale-up-active",
						enter: `slide-right`,
						enterActive: `slide-right-active`,
						exit: `slide-out-${this.state.lastVoteDirection}`,
						exitActive: `slide-out-${this.state.lastVoteDirection}-active`,
						exitDone: `slide-out-${this.state.lastVoteDirection}-active`,
					}}
				>
					<section id="scoutThing">
						{nukersDisplay}
						<Thing clipdata={firstSlugData} voteCallback={this.judgeClip} autoplay={true} adminFunctions={admin} />
					</section>
				</CSSTransition>
			</section>
		)
	}
}

if (jQuery('#weedApp').length) {
	ReactDOM.render(
		<Weed />,
		document.getElementById('weedApp')
	);
}