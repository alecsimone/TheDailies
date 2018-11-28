import React from "react";
import ReactDOM from 'react-dom';
import ContenderVoteBar from './ContenderVoteBar.jsx';
import {turnContenderDataIntoVoteData} from '../Scripts/global.js';

export default class LiveVoteBar extends React.Component{
	constructor() {
		super();
		this.state = {
			liveData,
		}
	}

	componentDidMount() {
		this.updateLiveVoteBar();
		window.setInterval(() => this.updateLiveVoteBar(), 3000);
	}

	updateLiveVoteBar() {
		jQuery.get({
			url: `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/live`,
			dataType: 'json',
			success: (data) => {
				this.setState({liveData: data});
			}
		});
	}

	render() {
		let voteData = turnContenderDataIntoVoteData(this.state.liveData);

		return(
			<section id="LiveVoteBar">
				<ContenderVoteBar voteData={voteData} />
			</section>
		)
	}
}

ReactDOM.render(
	<LiveVoteBar />,
	document.getElementById('liveVoteBarApp')
);