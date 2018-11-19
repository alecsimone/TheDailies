import React from "react";
import ReactDOM from 'react-dom';
import TopFive from './TopFive.jsx';
import {playAppropriatePromoSound, playAppropriateKillSound} from '../Scripts/sounds.js';

export default class Live extends React.Component{
	constructor() {
		super();
		this.state = {
			hasData: false,
			locallyCutSlugs: [],
		}
	}

	componentDidMount() {
		this.updateLive();
		window.setInterval(() => this.updateLive(), 3000);
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
				});
				this.setState({
					clips: data,
					hasData: true,
				});
			}
		});
	}

	cutPost(postID) {
		console.log(`Cutting ${postID}`);
	}

	promotePost(postID) {
		console.log(`Promoting ${postID}`);
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

		let admin = {};
		if (dailiesGlobalData.userData.userRole === "administrator") {
			admin.cut = this.cutPost;
			admin.promote = this.promotePost;
			admin.edit = true;
		}

		let contenderCounter = 0;
		let contenderComponents = this.state.clips.map(function(clipdata) {
			contenderCounter++;
			return(
				<div className="contender" key={contenderCounter}>
					<div className="contenderNumber">{`!vote${contenderCounter}`}</div>
				 	<TopFive key={clipdata.slug} clipdata={clipdata} adminFunctions={admin}/>
				</div>
			);
		});

		return(
			<section id="live">
				{contenderComponents}
			</section>
		)
	}
};

ReactDOM.render(
	<Live />,
	document.getElementById('liveApp')
);