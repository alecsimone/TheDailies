import React from "react";
import SlugTitle from './Things/SlugTitle.jsx';
import MetaBox from './Things/MetaBox.jsx';
import VotingMachine from './Things/VotingMachine.jsx';


export default class Pleb extends React.Component{
	constructor() {
		super();
		this.state = {
			votersLoading: true,
			voters: [],
		}
	}

	componentDidMount() {
		this.getVoters();
	}

	componentDidUpdate() {
		if (this.state.votersLoading) {
			this.getVoters();
		}
	}

	getVoters() {
		let queryURL = `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/clipvoters/slug=${this.props.clipdata.slug}`
		let currentState = this.state;
		let boundThis = this;
		jQuery.get({
			url: queryURL,
			dataType: 'json',
			success: function(data) {
				currentState.voters = data;
				currentState.votersLoading = false;
				boundThis.setState(currentState);
			}
		});
	}

	render() {
		let sourcePic = this.props.clipdata.sourcepic;
		if (!sourcePic) {
			if (this.props.clipdata.source[0].name !== "User Submits") {
				sourcePic = this.props.clipdata.source[0].logo;
			} else if (this.props.clipdata.stars[0].logo) { 
				sourcePic = this.props.clipdata.stars[0].logo;
			} else if (this.props.clipdata.thumb) {
				sourcePic = this.props.clipdata.thumb;
			} else { 
				sourcePic = `${dailiesGlobalData.thisDomain}/wp-content/uploads/2017/07/rl-logo-med.png`;
			}
		}
		if (sourcePic === "unknown") {
			sourcePic = `${dailiesGlobalData.thisDomain}/wp-content/uploads/2017/07/rl-logo-med.png`;
		}

		let voters;
		voters = <VotingMachine key={`votingMachine-${this.props.clipdata.slug}`} slug={this.props.clipdata.slug} voterData={this.props.clipdata.voters} />

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

		return(
			<div className="Pleb">
				<img className="plebPic" src={sourcePic} />
				<div className="hopefuls-meta">
					<div className="hopefuls-title"><SlugTitle slug={this.props.clipdata.slug} type={this.props.clipdata.type} title={this.props.clipdata.title} /></div>
					{voters}
					<MetaBox metaData={this.props.clipdata} />
				</div>
			</div>
		)
	}
}