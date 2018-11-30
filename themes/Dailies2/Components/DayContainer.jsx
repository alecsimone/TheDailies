import React from "react";
import Thing from './Thing.jsx';
import TopFive from './TopFive.jsx';
import Leader from './Leader.jsx';
import Pleb from './Pleb.jsx';

export default class DayContainer extends React.Component {
	render() {
		var date = this.props.dayData.date;
		var monthsArray = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
		if (date.day === '1' || date.day === '01' || date.day === '21' || date.day === '31') {
			var dayString = date.day + 'st';
		} else if (date.day === '2' || date.day === '02' || date.day === '22') {
			var dayString = date.day + 'nd';
		} else if (date.day === '3' || date.day === '03' || date.day === '23') {
			var dayString = date.day + 'rd'
		} else {
			var dayString = date.day + 'th';
		}
		if (dayString.charAt(0) === '0') {
			dayString = dayString.substring(1);
		}
		let adminFunctions = this.props.adminFunctions;
		var things = this.props.dayData.postDatas;
		function thingsByScore(a,b) {
			//let parsedA = JSON.parse(a);
			//let parsedB = JSON.parse(b);
			//let scoreA = parseFloat(parsedA.votecount, 10);
			//let scoreB = parseFloat(parsedB.votecount, 10);
			// let scoreA = parseFloat(a.votecount, 10);
			// let scoreB = parseFloat(b.votecount, 10);
			let scoreA = 0;
			if (Array.isArray(a.voters)) {
				a.voters.forEach((voter) => scoreA = scoreA + Number(voter.weight));
			}
			let scoreB = 0;
			if (Array.isArray(b.voters)) {
				b.voters.forEach((voter) => scoreB = scoreB + Number(voter.weight));
			}
			return scoreB - scoreA;
		}
		var thingsSorted = things.sort(thingsByScore);
		// var thingsArray = Object.keys(thingsSorted);
		let winner = false;
		let noms = [];
		let contenders = [];
		things.forEach( (thing) => {
			if (thing.tags) {
				thing.tags.forEach((tagObject) => {
					if (tagObject.slug == "winners") {
						winner = thing;
						return;
					}
				});
			}
			if (thing.categories === "Noms") {
				noms.push(thing);
			} else {
				contenders.push(thing);
			}
		});
		let winnerPost;
		if (winner) {
			winnerPost = <Leader clipdata={winner} key={winner.slug} adminFunctions={adminFunctions} autoplay={false} />
		}
		let nomComponents = noms.map((thing) => {
			return(
				<TopFive clipdata={thing} key={thing.slug} adminFunctions={adminFunctions} />
			)
		});
		let contenderComponents = contenders.map((thing) => {
			return(
				<Pleb clipdata={thing} key={thing.slug} adminFunctions={adminFunctions} />
			)
		});

		let nomHeader;
		if (nomComponents.length > 0) {
			nomHeader = <h3 className="dayContainerSectionHeader">Nominees</h3>;
		}
		let contenderHeader;
		if (contenderComponents.length > 0) {
			contenderHeader = <h3 className="dayContainerSectionHeader">Contenders</h3>;
		}
		return(
			<section className="dayContainer">
				<div className="daytitle">{monthsArray[date.month - 1].toUpperCase()} {dayString.toUpperCase()}</div>
				{winnerPost}
				{nomHeader}
				{nomComponents}
				{contenderHeader}
				{contenderComponents}
			</section>
		)
	}
}