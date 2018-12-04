import React from "react";
import ShowLoginBox from "../LoginBox.jsx";

export default class VotingMachine extends React.Component{
	constructor() {
		super();
		this.state = {
			localOnlyVote: false,
		}
	}

	vote(e) {
		if (dailiesGlobalData.userData.userID == 0) {
			ShowLoginBox();
			return;
		}
		let direction;
		let voters = Object.keys(this.props.voterData);
		let existingVote = false;
		voters.forEach( (key) => {
			if (this.props.voterData[key].name === dailiesGlobalData.userData.userName) {
				this.props.voterData[key].weight > 0 ? existingVote = "yea" : existingVote = "nay";
			}
		});
		if (jQuery(e.target).hasClass("yeaButton")) {
			if (existingVote === "yea") {
				this.setState({localOnlyVote: "unYea"});
			} else {
				this.setState({localOnlyVote: "yea"});
			}
			direction = 'yea';
		} else if (jQuery(e.target).hasClass("nayButton")) {
			if (existingVote === "nay") {
				this.setState({localOnlyVote: "unNay"});
			} else {
				this.setState({localOnlyVote: "nay"});
			}
			direction = 'nay';
		}
		e.target.blur();
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				action: 'slug_vote',
				slug: this.props.slug,
				direction,
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
		if (this.props.voteCallback) {
			this.props.voteCallback(e);
		}
	}

	componentDidUpdate() {
		if (this.state.localOnlyVote) {
			let voters = Object.keys(this.props.voterData);
			let userHasVoted = false;
			voters.forEach( (key) => {
				if (this.props.voterData[key].name === dailiesGlobalData.userData.userName) {
					userHasVoted = true;
					if ( (this.state.localOnlyVote === "yea" && this.props.voterData[key].weight > 0) || (this.state.localOnlyVote === "nay" && this.props.voterData[key].weight < 0) )
					this.setState({localOnlyVote: false});
				}
			});
			if ( !userHasVoted && (this.state.localOnlyVote === "unNay" || this.state.localOnlyVote === "unYea") ) {
				this.setState({localOnlyVote: false});
			}
		}
	}

	render() {
		// console.log(this.props.voterData);

		if (this.props.voterData == "loading") {
			return (
				<div className="votingMachine loadingVotes">
					Voters Loading...
				</div>
			);
		}
		
		if (typeof this.props.voterData !== "object" || this.props.voterData === null) {
			let nayVoters;
			let yeaVoters;
			return (
				<div className="votingMachine">
					<div className="nayVoters voterBubblePod">{nayVoters}</div>
					<img src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2018/07/votenay.png`} className={`nayButton voteButton${this.state.localOnlyVote === "nay" || this.state.localOnlyVote === "unNay" ? " spin" : ""}`} onClick={(e) => this.vote(e)}/>
					<p className="score">0</p>
					<img src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2018/07/voteyea.png`} className={`yeaButton voteButton${this.state.localOnlyVote === "yea" || this.state.localOnlyVote === "unYea" ? " spin" : ""}`} onClick={(e) => this.vote(e)}/>
					<div className="yeaVoters voterBubblePod">{yeaVoters}</div>
				</div>
			);
		}
		let voters = Object.keys(this.props.voterData);

		let score = 0;
		let yeaVotersList = [];
		let nayVotersList = [];
		voters.forEach( (key) => {
			let thisScore = Number(this.props.voterData[key].weight);
			score = score + thisScore;
			if (thisScore > 0) {
				yeaVotersList.push(this.props.voterData[key]);
			} else {
				nayVotersList.push(this.props.voterData[key]);
			}
		});

		// console.log(yeaVotersList, nayVotersList);

		let ourVote;

		let yeaVoters = Object.keys(yeaVotersList).map((key) => {
			var voterName = yeaVotersList[key]['name'];
			if (voterName === dailiesGlobalData.userData.userName) {ourVote = "yea";}
			var voterPic = yeaVotersList[key]['picture'];
			let voterRep = yeaVotersList[key]['weight'];
			return (
				<img key={key} className="voterBubble" src={voterPic} title={`${voterName}: ${voterRep}`} onError={(e) => window.imageError(e, 'twitchVoter')} />
			)
		});

		let nayVoters = Object.keys(nayVotersList).map((key) => {
			var voterName = nayVotersList[key]['name'];
			if (voterName === dailiesGlobalData.userData.userName) {ourVote = "nay";}
			var voterPic = nayVotersList[key]['picture'];
			let voterRep = nayVotersList[key]['weight'];
			return (
				<img key={key} className="voterBubble" src={voterPic} title={`${voterName}: ${voterRep}`} onError={(e) => window.imageError(e, 'twitchVoter')} />
			)
		});

		return (
			<div className="votingMachine">
				<div className="nayVoters voterBubblePod">{nayVoters}</div>
				<img src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2018/07/votenay.png`} className={`nayButton voteButton${(this.state.localOnlyVote === "nay" || this.state.localOnlyVote === "unNay") ? " spin" : ""} ${ourVote === "nay" ? " currentlyChosenVoteButton" : ""}`} onClick={!this.state.localOnlyVote ? (e) => this.vote(e) : () => {console.log("we're still processing your vote")}}/>
				<p className="score">{score > 0 ? `+${score}` : `${score}`}</p>
				<img src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2018/07/voteyea.png`} className={`yeaButton voteButton${(this.state.localOnlyVote === "yea" || this.state.localOnlyVote === "unYea") ? " spin" : ""} ${ourVote === "yea" ? " currentlyChosenVoteButton" : ""}`} onClick={!this.state.localOnlyVote ? (e) => this.vote(e) : () => {console.log("we're still processing your vote")}}/>
				<div className="yeaVoters voterBubblePod">{yeaVoters}</div>
			</div>
		);
	}
};