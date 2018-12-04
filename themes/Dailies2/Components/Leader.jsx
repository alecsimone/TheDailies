import React from "react";
import ClipPlayer from './ClipPlayer.jsx';
import VoterInfoBox from './VoterInfoBox.jsx';
import WeedComments from './WeedComments.jsx';
import KeepBar from './KeepBar.jsx';
import VotingMachine from './Things/VotingMachine.jsx';
import SlugTitle from './Things/SlugTitle.jsx';
import MetaBox from './Things/MetaBox.jsx';
import AdminBox from './Things/AdminBox.jsx';


export default class Leader extends React.Component{
	constructor() {
		super();
		this.state = {
			comments: [],
			commentsLoading: true,
			voters: [],
			votersLoading: true,
		}

		this.postComment = this.postComment.bind(this);
		this.yeaComment = this.yeaComment.bind(this);
		this.delComment = this.delComment.bind(this);
		this.cutHandler = this.cutHandler.bind(this);
	}

	componentDidMount() {
		this.getComments();
		// this.getVoters();
	}

	componentDidUpdate(prevProps) {
		if (prevProps.clipdata.slug !== this.props.clipdata.slug) {
			this.getComments();
		}
		if (this.state.votersLoading) {
			// this.getVoters();
		}
	}

	getComments() {
		console.log(`getting new comments for ${this.props.clipdata.slug}`);
		let queryURL = `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/clipcomments/slug=${this.props.clipdata.slug}`
		let currentState = this.state;
		let boundThis = this;
		jQuery.get({
			url: queryURL,
			dataType: 'json',
			success: function(data) {
				currentState.comments = data;
				currentState.commentsLoading = false;
				boundThis.setState(currentState);
			}
		});
	}

	// getVoters() {
	// 	let queryURL = `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/clipvoters/slug=${this.props.clipdata.slug}`
	// 	let currentState = this.state;
	// 	let boundThis = this;
	// 	jQuery.get({
	// 		url: queryURL,
	// 		dataType: 'json',
	// 		success: function(data) {
	// 			currentState.voters = data;
	// 			currentState.votersLoading = false;
	// 			boundThis.setState(currentState);
	// 		}
	// 	});
	// }

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
				slug: this.props.clipdata.slug,
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
					slug: boundThis.props.clipdata.slug,
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

	cutHandler(e) {
		let vodLink = this.props.clipdata.vodlink;
		if (vodLink === "null") {
			var VODBase = "null";
			var VODTime = "null";
		} else {
			var timestampIndex = vodLink.lastIndexOf('t=');
			var VODBase = vodLink.substring(29, timestampIndex - 1);
			var VODTime = window.vodLinkTimeParser(vodLink);
		}
		let slugObj = {
			slug: this.props.clipdata.slug,
			createdAt: Date.parse(this.props.clipdata.age),
			cutBoolean: true,
			VODBase,
			VODTime,
		}
		var scope = "all";
		this.props.cutSlug(slugObj, scope);
	}

	render() {
		let voters;
		// if (this.state.votersLoading) {
		// 	voters = "Voters Loading..."
		// } else {	
		// 	// voters = <VoterInfoBox key={`voterInfoBox-${this.props.clipdata.slug}`} thisID={this.props.clipdata.slug} voterData={this.state.voters} twitchVoters={[]} guestlist={[]} addedVotes="0" />
		// 	voters = <VotingMachine key={`votingMachine-${this.props.clipdata.slug}`} slug={this.props.clipdata.slug} voterData={this.state.voters} />
		// }
		voters = <VotingMachine key={`votingMachine-${this.props.clipdata.slug}`} slug={this.props.clipdata.slug} voterData={this.props.clipdata.voters} voteCallback={this.props.voteCallback}/>
		let adminControls;
		if (dailiesGlobalData.userData.userRole === 'administrator') {
			adminControls = <div id="hopefuls-admin-controls"><img className="cutButton" src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2017/04/red-x.png`} onClick={this.cutHandler} /><KeepBar slug={this.props.clipdata.slug} keepSlug={this.props.keepSlug} vodLink={this.props.clipdata.vodlink} clipTime={this.props.clipdata.age} voters={this.state.voters} source={this.props.clipdata.source} sourcePic={this.props.clipdata.sourcepic} /></div>;
		}

		let width = jQuery(window).width();
		let playerWidth;
		if (width > 1270) {
			playerWidth = width * .95 / 2 - 12;
		} else {
			playerWidth = width;
		}

		let link;
		if (this.props.clipdata.type === "twitch") {
			link = `https://clips.twitch.tv/${this.props.clipdata.slug}`;
		} else if (this.props.clipdata.type === "youtube" || this.props.clipdata.type === "ytbe") {
			link = `https://www.youtube.com/watch?v=${this.props.clipdata.slug}`;
		} else if (this.props.clipdata.type === "gfycat") {
			link = `https://gfycat.com/${this.props.clipdata.slug}`;
		} else if (this.props.clipdata.type === "twitter") {
			link = `https://twitter.com/statuses/${this.props.clipdata.slug}`;
		}

		let identifier;
		if (this.props.clipdata.postID) {
			identifier = this.props.clipdata.postID;
		} else {
			identifier = this.props.clipdata.slug;
		}

		let winner;
		let tags = this.props.clipdata.tags;
		if (tags) {
			tags.forEach((tagObject) => {
				if (tagObject.slug == "winners") {
					winner = true;
				}
			})
			if (winner) {
				winner = <div className="winnerBannerBox"><img src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2018/11/Winner-banner-wide.jpg`} className="winnerBanner" /></div>;
			}
		}

		return(
			<div className="Leader">
				{winner}
				<div className="playerContainer">
					<ClipPlayer type={this.props.clipdata.type} slug={this.props.clipdata.slug} width={playerWidth} vodlink={this.props.clipdata.vodlink} autoplay={this.props.autoplay} />
				</div>
				<div className="hopefuls-meta">
					<div className="hopefuls-title"><SlugTitle slug={this.props.clipdata.slug} type={this.props.clipdata.type} title={this.props.clipdata.title} /></div>
					{voters}
					<MetaBox metaData={this.props.clipdata} />
					<WeedComments key={`weedComments-${this.props.clipdata.slug}`} slug={this.props.clipdata.slug} postComment={this.postComment} commentsLoading={this.state.commentsLoading} comments={this.state.comments} yeaComment={this.yeaComment} delComment={this.delComment} />
				</div>
				<AdminBox identifier={identifier} adminFunctions={this.props.adminFunctions} />
			</div>
		)
	}
}