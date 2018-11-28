import React from "react";
import ReactDOM from 'react-dom';
import VotingMachine from './Things/VotingMachine.jsx';

export default class LiveVotingMachine extends React.Component{
	constructor() {
		super();
		this.state = {
			voters: liveVoters,
		}
		console.log(this.state.voters);
	}

	componentDidMount() {
		this.updateLiveVoters();
		window.setInterval(() => this.updateLiveVoters(), 3000);
	}

	updateLiveVoters() {
		jQuery.get({
			url: `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/live-voters`,
			dataType: 'json',
			success: (data) => {
				this.setState({
					voters: data,
				});
			}
		});
	}

	render() {
		return(
			<section id="LiveVotingMachine">
				<VotingMachine key="votingMachine-live" slug="live" voterData={this.state.voters} />
			</section>
		)
	}
}

ReactDOM.render(
	<LiveVotingMachine />,
	document.getElementById('liveVotingMachineApp')
);