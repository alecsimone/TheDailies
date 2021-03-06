import React from "react";
import ReactDOM from 'react-dom';

export default class Userbox extends React.Component {
	render() {
			if (this.props.userData.userID != '0') {
				let thisDomain = dailiesGlobalData.thisDomain;
				let giveableRepNumber = parseInt(this.props.userData.giveableRep, 10);			
				if (!isNaN(giveableRepNumber)) {
					var giveableRep = this.props.userData.giveableRep;
				} else {
					var giveableRep = 1;
				}
				var userboxElements = (
					<div id="userbox-links">
						<p className="userbox">Giveable Rep: {giveableRep}</p>
						<p className="userbox"><a href={thisDomain + "/your-votes"}>Your Votes</a></p>
						<p className="userbox"><a href={thisDomain + "/secret-garden"}>Secret Garden</a></p>
						<p className="userbox"><a href={dailiesGlobalData.logoutURL}>Logout</a></p>
					</div>
				)
			} else {
				var userboxElements = (
					<div id="userbox-links">
						<p className="userbox">Your votes count as much as your Rep. New members get 10</p>
						<p className="userbox">Vote daily and your Rep will grow</p>
					</div>
				);
				jQuery('#wp-social-login').appendTo('#userbox-links');
			}
			let repNumber = parseInt(this.props.userData.userRep, 10);			
			if (!isNaN(repNumber)) {
				var rep = this.props.userData.userRep;
			} else {
				var rep = 1;
			} 

		return(
			<div id="userbox">
				<header id="repHeader"> 
					<img className="userboxProfilePic" src={dailiesGlobalData.userData.userPic} /> 
					Your Rep: {rep}
				</header>
				{userboxElements}
			</div>
		)
	}
}