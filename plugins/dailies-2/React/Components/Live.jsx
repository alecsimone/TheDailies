import React from "react";
import ReactDOM from 'react-dom';
import LittleThing from './Things/LittleThing.jsx';
import ContenderVoteBar from './ContenderVoteBar.jsx';
import {playAppropriatePromoSound, playAppropriateKillSound} from '../Scripts/sounds.js';
import {turnContenderDataIntoVoteData} from '../Scripts/global.js';

export default class Live extends React.Component{
	constructor() {
		super();
		this.state = {
			hasData: false,
			locallyCutSlugs: [],
			editableTitles: [],
		}

		this.cutPost = this.cutPost.bind(this);
		this.makeTitleEditable = this.makeTitleEditable.bind(this);
		this.submitNewTitle = this.submitNewTitle.bind(this);
		this.editTitle = this.editTitle.bind(this);
	}

	componentDidMount() {
		this.updateLive();
		if (dailiesGlobalData.userData.userRole == "administrator") {
			window.setInterval(() => this.updateLive(), 1000);
		} else {
			window.setInterval(() => this.updateLive(), 3000);
		}
	}

	updateLive() {
		jQuery.get({
			url: `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/live`,
			dataType: 'json',
			success: (data) => {
				let locallyCutSlugs = this.state.locallyCutSlugs;
				data.forEach((post, index) => {
					if (locallyCutSlugs.indexOf(post.slug) > -1) {
						data.splice(index, 1);
					}
					if (this.state.editableTitles.indexOf(post.postID) > -1) {
						let currentStateEditableTitleIndex = this.state.clips.findIndex((clipdata) => clipdata.postID === post.postID);
						data[index].title = this.state.clips[currentStateEditableTitleIndex].title;
					}
				});
				this.setState({
					clips: data,
					hasData: true,
				});
			}
		});
	}

	hidePost(postID) {
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				id: postID,
				action: 'eliminate_post',
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

	cutPost(postID) {
		console.log(`Cutting ${postID}`);
		let clips = this.state.clips;
		clips.forEach((clipdata, index) => {
			if (clipdata.postID == postID) {
				delete clips[index];
			}
		});
		let locallyCutSlugs = this.state.locallyCutSlugs;
		locallyCutSlugs.push(postID);
		this.setState({clips, locallyCutSlugs})
		playAppropriateKillSound();
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				id: postID,
				action: 'post_demoter',
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

	promotePost(postID) {
		console.log(`Promoting ${postID}`);
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				id: postID,
				action: 'post_promoter',
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

	highlightPost(e, slug) {
		let checkBox = jQuery(e.target);
		let contenderToHighlight = checkBox.closest(".contender");
		if (e.target.checked) {
			playAppropriatePromoSound();
			contenderToHighlight.addClass("highlight");
		} else {
			contenderToHighlight.removeClass("highlight");
		}
	}

	makeTitleEditable(e, identifier) {
		e.preventDefault();
		let editableTitles = this.state.editableTitles;
		let titleIsEditable = editableTitles.findIndex((id) => id === identifier);
		if (titleIsEditable > -1) {
			let editableTitleToRemoveIndex = editableTitles.findIndex((editablePostID) => editablePostID === identifier);
			editableTitles.splice(editableTitleToRemoveIndex, 1);
		} else {
			editableTitles.push(identifier);
		}
		this.setState({editableTitles});
	}

	submitNewTitle(e, slug) {
		if (e.which === 13) {
			e.preventDefault();
			let clipsIndex = this.state.clips.findIndex((clipdata) => clipdata.slug === slug);
			let postID = this.state.clips[clipsIndex].postID;
			jQuery.ajax({
				type: "POST",
				url: dailiesGlobalData.ajaxurl,
				dataType: 'json',
				data: {
					postID,
					newTitle: e.target.value,
					action: 'edit_live_title',
				},
				error: function(one, two, three) {
					console.log(one);
					console.log(two);
					console.log(three);
				},
				success: (data) => {
					let editableTitles = this.state.editableTitles;
					let editableTitleToRemoveIndex = editableTitles.findIndex((editablePostID) => editablePostID === postID);
					editableTitles.splice(editableTitleToRemoveIndex, 1);
					this.setState({editableTitles});
				}
			});
		}
	}

	editTitle(e, slug) {
		let clips = this.state.clips;
		let clipsIndexToEdit = clips.findIndex((clipdata) => slug === clipdata.slug);
		clips[clipsIndexToEdit].title = e.target.value;
		this.setState({clips});
	}

	resetLive() {
		var date = Date.now();
		if (confirm('Are you sure you want to reset the posts?')) {
			console.log("OK, we'll reset them.");
			this.setState({clips: []});
			jQuery.ajax({
				type: "POST",
				url: dailiesGlobalData.ajaxurl,
				dataType: 'json',
				data: {
					timestamp: date,
					action: 'reset_live',
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
		} else {
			console.log("OK cool, we'll just keep these then.");
		}
	}

	render() {
		if (!this.state.hasData) {
			return(
				<section id="live" className="noPosts">
					<div>
						<div>Getting Contenders...</div>
						<div className="lds-ring"><div></div><div></div><div></div><div></div></div>
					</div>
				</section>
			); 
		}
		if (this.state.clips.length === 0) {
			return(
				<section id="live" className="noPosts">
					<div>There are no contenders yet! Want to <a href="https://dailies.gg/submit/">submit</a> one?</div>
				</section>
			);
		}

		let voteData = turnContenderDataIntoVoteData(this.state.clips);

		let admin = {};
		if (dailiesGlobalData.userData.userRole === "administrator") {
			// admin.cut = this.cutPost;
			admin.cut = this.hidePost;
			admin.promote = this.promotePost;
			admin.toggle = this.highlightPost;
			admin.edit = this.makeTitleEditable;
		}

		let contenderCounter = 0;
		let component = this;
		let contenderComponents = this.state.clips.map(function(clipdata) {
			contenderCounter++;
			return(
				<article className={`contender${clipdata.eliminated === "true" ? " eliminated" : ""}`} key={contenderCounter}>
					<div className="contenderNumber">{`!vote${contenderCounter}`}</div>
				 	<LittleThing key={clipdata.slug} clipdata={clipdata} adminFunctions={admin} editableTitle={component.state.editableTitles.includes(clipdata.postID) ? true : false} submitNewTitle={component.submitNewTitle} editTitle={component.editTitle} />
				</article>
			);
		});

		if (dailiesGlobalData.userData.userID === 1) {
			var resetLiveButton = <div className="resetContainer"><img className="resetLive" onClick={() => this.resetLive()} src={dailiesGlobalData.thisDomain + '/wp-content/uploads/2017/12/reset-icon.png'} /></div>
		} else {
			var resetLiveButton = '';
		}

		return(
			<section id="live">
				<ContenderVoteBar voteData={voteData} />
				{contenderComponents}
				{resetLiveButton}
			</section>
		)
	}
};

ReactDOM.render(
	<Live />,
	document.getElementById('liveApp')
);