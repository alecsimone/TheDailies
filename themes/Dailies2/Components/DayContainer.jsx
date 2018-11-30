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
		var thingComponents = things.map((thing) => {
			let winner = false;
			let tags = thing.tags;
			if (tags) {
				tags.forEach((tagObject) => {
					if (tagObject.slug == "winners") {
						winner = true;
					}
				});
			}
			if (winner) {
				return(
					<Leader clipdata={thing} key={thing.slug} adminFunctions={adminFunctions} autoplay={false} />
				)
			} else if (thing.categories === "Noms") {
				return(
					<TopFive clipdata={thing} key={thing.slug} adminFunctions={adminFunctions} />
				)
			} else {
				return(
					<Pleb clipdata={thing} key={thing.slug} adminFunctions={adminFunctions} />
				)
			}
		})
		return(
			<section className="dayContainer">
				<div className="daytitle">{monthsArray[date.month - 1].toUpperCase()} {dayString.toUpperCase()}</div>
				{thingComponents}
			</section>
		)
	}
}