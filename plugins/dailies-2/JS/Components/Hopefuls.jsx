import React from "react";
import ReactDOM from 'react-dom';
import Thing from './Things/Thing.jsx';
import LittleThing from './Things/LittleThing.jsx';
import TinyThing from './Things/TinyThing.jsx';
import {playAppropriatePromoSound, playAppropriateKillSound} from '../Scripts/sounds.js';

export default class Hopefuls extends React.Component{
	constructor() {
		super();
		this.state = {
			hasData: false,
			liveSlug: "false",
			lastPromotedSlug: "none",
			locallyCutSlugs: [],
		}

		this.keepSlug = this.keepSlug.bind(this);
		this.cutSlug = this.cutSlug.bind(this);
		this.makeLive = this.makeLive.bind(this);
	}

	keepSlug(newThingName, slug) {
		let clips = this.state.clips;
		let locallyCutSlugs = this.state.locallyCutSlugs;
		clips.shift();
		locallyCutSlugs.push(slug);
		this.setState({
			clips,
			locallyCutSlugs,
		});
		playAppropriatePromoSound();
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				action: 'keepSlug',
				newThingName,
				slug,
			},
			error: function(one, two, three) {
				console.log(one);
				console.log(two);
				console.log(three);
			},
			success: (data) => {
				console.log(data);
			},
		});
	}

	// keepSlug(slugObj, thingData) {
	// 	console.log(slugObj);
	// 	var currentState = this.state;
	// 	currentState.clips.shift();
	// 	this.setState(currentState);
	// 	window.playAppropriatePromoSound();
	// 	var page = this;
	// 	jQuery.ajax({
	// 		type: "POST",
	// 		url: dailiesGlobalData.ajaxurl,
	// 		dataType: 'json',
	// 		data: {
	// 			action: 'keepSlug',
	// 			slugObj,
	// 			thingData,
	// 		},
	// 		error: function(one, two, three) {
	// 			console.log(one);
	// 			console.log(two);
	// 			console.log(three);
	// 		},
	// 		success: function(data) {
	// 			console.log(data);
	// 			if (Number.isInteger(data)) {
	// 				//window.open(dailiesGlobalData.thisDomain + '/wp-admin/post.php?post=' + data + '&action=edit', '_blank');
	// 				jQuery.ajax({
	// 					type: "POST",
	// 					url: dailiesGlobalData.ajaxurl,
	// 					dataType: 'json',
	// 					data: {
	// 						action: 'addSourceToPost',
	// 						channelURL: slugObj.channelURL,
	// 						channelPic: slugObj.channelPic,
	// 						postID: data,
	// 					},
	// 					error: function(one, two, three) {
	// 						console.log(one);
	// 						console.log(two);
	// 						console.log(three);
	// 					},
	// 					success: function(data) {
	// 						console.log(data);
	// 					}
	// 				});
	// 			}
	// 		}
	// 	});
	// }

	componentDidMount() {
		this.updateHopefuls();
		window.setInterval(() => this.updateHopefuls(), 3000);
	}

	updateHopefuls() {
		jQuery.get({
			url: `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/hopefuls`,
			dataType: 'json',
			success: (data) => {
				let locallyCutSlugs = this.state.locallyCutSlugs;
				let liveSlugStillLive = false;
				data.clips.forEach((hopeful, index) => {
					if (this.state.liveSlug === hopeful.slug) {liveSlugStillLive = true;}
					if (locallyCutSlugs.indexOf(hopeful.slug) > -1) {
						data.clips.splice(index, 1);
					}
				});
				let alreadyPlayedAPromoSound = false;
				if (this.state.lastPromotedSlug != data.lastPromotedSlug && this.state.lastPromotedSlug !== "none") {
					window.playAppropriatePromoSound();
					alreadyPlayedAPromoSound = true;
				}
				this.sortHopefuls(data.clips);
				this.setState({
					clips: data.clips,
					liveSlug: alreadyPlayedAPromoSound ? "false" : data.liveSlug,
					hasData: true,
					lastPromotedSlug: data.lastPromotedSlug,
					pulledClipsCount: data.pulledClipsCount,
					pulledClipsVotecount: data.pulledClipsVotecount,
				});
			}
		});
	}

	sortHopefuls(hopefulsData) {
		let liveSlug = this.state.liveSlug;
		hopefulsData.sort(function(a,b) {
			if (a.slug == liveSlug) {return -1;}
			if (b.slug == liveSlug) {return 1;}
			let timeA = new Date(a.age).getTime();
			let timeB = new Date(b.age).getTime();
			if (timeA === timeB) {
				let scoreA = Number(a.score);
				let scoreB = Number(b.score);
				return scoreB - scoreA;
			}
			return timeA - timeB;
			// let timeA = new Date(a.age).getTime();
			// let timeB = new Date(b.age).getTime();
			// if (timeA === timeB) {
			// 	let scoreA = Number(a.score);
			// 	let scoreB = Number(b.score);
			// 	return scoreB - scoreA;
			// }
			// return timeA - timeB;
		});
		return hopefulsData;
	}

	cutSlug(slug) {
		if (this.state.liveSlug === slug) {
			window.toggled = false;
		}
		var clips = this.state.clips;
		clips.shift();
		let locallyCutSlugs = this.state.locallyCutSlugs;
		locallyCutSlugs.push(slug);
		this.setState({
			clips,
			locallyCutSlugs,
			liveSlug: this.state.liveSlug === slug ? false : this.state.liveSlug,
		});
		window.playAppropriateKillSound();
		console.log(slug);
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				action: 'hopefuls_cutter',
				slug: slug,
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

	makeLive(e, slug) {
		jQuery("#liveSlugDimmer").toggleClass("dark");
		jQuery("#topfive").toggleClass("dim");
		window.toggled = true;
		let checkboxes = document.getElementsByClassName('checkbox');
		for (var i = 0; i < checkboxes.length; i++) {
			if (e.target !== checkboxes[i]) {
				checkboxes[i].checked = false
			}
		};
		if (this.state.liveSlug == slug) {
			slug = false;
			window.toggled = false;
		}
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				action: 'choose_live_slug',
				slug,
			},
			error: function(one, two, three) {
				console.log(one);
				console.log(two);
				console.log(three);
			},
			success: function(data) {
				// console.log(data);
			}
		});
	}

	render() {
		if (!this.state.hasData) {
			return(
				<section id="hopefuls" className="noPosts">
					<div>
						<div>Getting Hopefuls...</div>
						<div className="lds-ring"><div></div><div></div><div></div><div></div></div>
					</div>
				</section>
			); 
		}
		if (this.state.clips.length === 0) {
			return(
				<section id="hopefuls" className="noPosts">
					<div>There are no hopefuls yet! Maybe go do some <a href={`${dailiesGlobalData.thisDomain}/1r`}>scouting</a> and find us some?</div>
				</section>
			);
		}

		let admin = {};
		if (dailiesGlobalData.userData.userRole === "administrator") {
			admin.cut = this.cutSlug;
			admin.keep = this.keepSlug;
			admin.toggle = this.makeLive;
			admin.toggled = this.state.liveSlug;
		}

		let leader = this.state.clips[0];
		let topfive = [];
		for (var i = 1; i < 7 && i < this.state.clips.length; i++) {
			topfive.push(this.state.clips[i]);
		}
		let topfivecomponents = topfive.map(function(clipdata) {
			return <LittleThing key={clipdata.id} clipdata={clipdata} adminFunctions={admin} />;
		});
		let plebs = [];
		for (var i = 7; i < this.state.clips.length; i++) {
			plebs.push(this.state.clips[i]);
		}
		let plebcomponents = plebs.map(function(clipdata) {
			return <TinyThing key={clipdata.id} clipdata={clipdata} />;
		});
		let adminToggles = document.getElementsByClassName("checkbox");
		window.toggled = false;
		for (let toggle of adminToggles) {
			if (toggle.checked) {window.toggled = true;}
		}
		console.log(window.toggled);

		return(
			<section id="hopefuls">
			<div id="liveSlugDimmer" className={((this.state.liveSlug == false || dailiesGlobalData.userData.userRole !== "administrator") || !window.toggled) ? "" : "dark"} />
				<div id="hopefulsIntroData">
					<h5>{this.state.pulledClipsCount} plays, <span className="lightbluetext">{this.state.pulledClipsVotecount} votes</span>, <span className="goldtext">{this.state.clips.length} Hopefuls.</span></h5>
				</div>
				<div id="leader">
					<Thing key={leader.id} clipdata={leader} adminFunctions={admin} autoplay={false} />
				</div>
			<div id="topfive" className={((this.state.liveSlug == false || dailiesGlobalData.userData.userRole !== "administrator") || !window.toggled) ? "" : "dim"}>
					{topfivecomponents}
				</div>
			<div id="plebs" className={((this.state.liveSlug == false || dailiesGlobalData.userData.userRole !== "administrator") || !window.toggled) ? "" : "dim"}>
					{plebcomponents}
				</div>
			</section>
		)
	}
}

ReactDOM.render(
	<Hopefuls />,
	document.getElementById('hopefulsApp')
);