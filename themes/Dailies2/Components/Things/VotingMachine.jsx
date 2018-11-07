import React from "react";

export default class VotingMachine extends React.Component{
	constructor() {
		super();
		this.state = {
			localOnlyVote: false,
		}
	}

	vote(e) {
		let direction;
		if (jQuery(e.target).hasClass("yeaButton")) {
			this.setState({localOnlyVote: "yea"});
			direction = 'yea';
		} else if (jQuery(e.target).hasClass("nayButton")) {
			this.setState({localOnlyVote: "nay"});
			direction = 'nay';
		}
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
			voters.forEach( (key) => {
				if (this.props.voterData[key].name === dailiesGlobalData.userData.userName) {
					if ( (this.state.localOnlyVote === "yea" && this.props.voterData[key].weight > 0) || (this.state.localOnlyVote === "nay" && this.props.voterData[key].weight < 0) )
					this.setState({localOnlyVote: false});
				}
			});
		}
	}

	render() {
		// console.log(this.props.voterData);

		if (this.props.voterData == "loading") {
			return (
				<div className="votingMachine">
					Voters Loading...
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

		let yeaVoters = Object.keys(yeaVotersList).map((key) => {
			var voterName = yeaVotersList[key]['name'];
			var voterPic = yeaVotersList[key]['picture'];
			let voterRep = yeaVotersList[key]['weight'];
			return (
				<img key={key} className="voterBubble" src={voterPic} title={`${voterName}: ${voterRep}`} onError={(e) => window.imageError(e, 'twitchVoter')} />
			)
		});

		let nayVoters = Object.keys(nayVotersList).map((key) => {
			var voterName = nayVotersList[key]['name'];
			var voterPic = nayVotersList[key]['picture'];
			let voterRep = nayVotersList[key]['weight'];
			return (
				<img key={key} className="voterBubble" src={voterPic} title={`${voterName}: ${voterRep}`} onError={(e) => window.imageError(e, 'twitchVoter')} />
			)
		});

		return (
			<div className="votingMachine">
				{nayVoters}
				<img src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2018/07/votenay.png`} className={`nayButton voteButton${this.state.localOnlyVote === "nay" ? " spin" : ""}`} onClick={(e) => this.vote(e)}/>
				<p className="score">{score > 0 ? `+${score}` : `${score}`}</p>
				<img src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2018/07/voteyea.png`} className={`yeaButton voteButton${this.state.localOnlyVote === "yea" ? " spin" : ""}`} onClick={(e) => this.vote(e)}/>
				{yeaVoters}
			</div>
		);
	}
};