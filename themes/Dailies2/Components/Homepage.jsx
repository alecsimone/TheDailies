import React from "react";
import ReactDOM from 'react-dom';
import HomeTop from './HomeTop.jsx';
import Thing from './Thing.jsx';
import Leader from './Leader.jsx';
import DayContainer from './DayContainer.jsx';

class Homepage extends React.Component {
	constructor() {
		super();
		this.state = {
			winner: dailiesMainData.firstWinner,
			dayContainers: {
				0: dailiesMainData.dayOne,
			},
		}
		this.state.user = dailiesGlobalData.userData;
		this.handleScroll = this.handleScroll.bind(this);
		window.addEventListener("scroll", this.handleScroll);
	}

	componentDidMount() {
		window.setInterval(() => this.updateVotes(), 3000);
	}

	updateVotes() {
		let slugs = [];
		slugs.push(this.state.winner.slug);
		let dayContainers = Object.keys(this.state.dayContainers);
		dayContainers.forEach( (key) => {
			this.state.dayContainers[key].postDatas.forEach( (data) => {
				slugs.push(data.slug);
			});
		});
		let sluglist = '';
		slugs.forEach((slug) => {sluglist += `${slug},`;});
		sluglist = sluglist.substring(0, sluglist.length - 1);
		let sluglistVotersQuery = `${dailiesGlobalData.thisDomain}/wp-json/dailies-rest/v1/sluglistVoters/slugs=${sluglist}`;
		jQuery.get({
			url: sluglistVotersQuery,
			dataType: 'json',
			success: (data) => {
				let winner = this.state.winner;
				winner.voters = data[winner.slug];
				this.setState({winner});
				let dayContainers = this.state.dayContainers;
				let dayContainerKeys = Object.keys(dayContainers);
				dayContainerKeys.forEach( (key) => {
					dayContainers[key].postDatas.forEach( (postData, postKey) => {
						let thisSlug = postData.slug;
						dayContainers[key].postDatas[postKey].voters = data[thisSlug];
					})
				});
				this.setState({dayContainers});
			},
		});
	}

	handleScroll() {
		var windowHeight = jQuery(window).height();
		var pageHeight = jQuery(document).height();
		var scrollTop = jQuery(window).scrollTop();
		if (scrollTop + 2 * windowHeight > pageHeight && !this.state.loadingMore) {
			this.setState({
				loadingMore: true,
			});
			let dayContainerCount = Object.keys(this.state.dayContainers).length;
			let lastDayContainer = this.state.dayContainers[dayContainerCount - 1];
			var currentDay = lastDayContainer['date']['day'];
			if (currentDay < 10 && currentDay.charAt(0) !== '0') {
				currentDay = '0' + currentDay;
			}
			var currentMonth = lastDayContainer['date']['month'];
			if (currentMonth < 10 && currentMonth.charAt(0) !== '0') {
				currentMonth = '0' + currentMonth;
			}
			var currentYear = lastDayContainer['date']['year'];
			var currentDayObject = new Date(currentYear, currentMonth-1, currentDay);

			this.stepBackDayAndQuery(currentDayObject, currentYear, currentMonth, currentDay);
		}
	}

	stepBackDayAndQuery(currentDayObject, currentYear, currentMonth, currentDay) {
		var newDayObject = currentDayObject - 1000 * 60 * 60 * 24;
		newDayObject = new Date(newDayObject);
		let newYear = newDayObject.getFullYear().toString();
		let newMonth = newDayObject.getMonth() + 1;
		if (newMonth < 10) {
			newMonth = '0' + newMonth;
		} else {
			newMonth = newMonth.toString();
		}
		let newDay = newDayObject.getDate();
		if (newDay < 10) {
			newDay = '0' + newDay;
		} else {
			newDay = newDay.toString();
		}
		let currentFormattedDate = currentYear + '-' + currentMonth + '-' + currentDay;
		let nextFormattedDate = newYear + '-' + newMonth + '-' + newDay;
		let nextDateQuery = dailiesGlobalData.thisDomain + '/wp-json/wp/v2/posts?after=' + nextFormattedDate + 'T00:00:00&before=' + currentFormattedDate + 'T00:00:00';
		jQuery.get({
			url: nextDateQuery,
			dataType: 'json',
			success: function(data) {
				if (data.length === 0) {
					this.stepBackDayAndQuery(newDayObject, newYear, newMonth, newDay);
				} else {
					let newPostDatas = [];
					jQuery.each(data, function(index, allData) {
						newPostDatas.push(allData.clipdata);;
					});
					let newDate = {
						day: newDay,
						month: newMonth,
						year: newYear,
					};
					let oldDayContainers = this.state.dayContainers;
					let dayContainerCounter = Object.keys(oldDayContainers).length;
					oldDayContainers[dayContainerCounter] = {
						date: newDate,
						postDatas: newPostDatas,
					}
					this.setState({
						dayContainers: oldDayContainers,
						loadingMore: false,
					});
				}
			}.bind(this)
		});
	}

	declareWinner(postID) {
		console.log(postID);
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				id: postID,
				action: 'declare_winner',
				vote_nonce: dailiesMainData.nonce,
			},
			error: function(one, two, three) {
				console.log(one);
				console.log(two);
				console.log(three);
			},
			success: function(data) {
				// console.log(this.props);
			}
		});
	}

	addScore(addedPoints, identifier) {
		if (!isNaN(addedPoints)) {
			jQuery.ajax({
				type: "POST",
				url: dailiesGlobalData.ajaxurl,
				dataType: 'json',
				data: {
					action: 'add_twitter_votes',
					id: identifier,
					addedPoints,
					vote_nonce: dailiesMainData.nonce,
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
	}

	render() {
		let admin = {};
		if (dailiesGlobalData.userData.userRole == "administrator") {
			admin.edit = true;
			admin.promote = this.declareWinner;
			admin.input = this.addScore;
		}
		var userData = this.state.user;
		var winnerVoteData = dailiesMainData.firstWinner.voteData;
		var dayContainers = this.state.dayContainers;
		var dayContainersArray = Object.keys(dayContainers);
		var dayContainerComponents = dayContainersArray.map(function(key) {
			if (dayContainers[key]['postDatas'].length > 0) {
				if (dayContainers[key]['date']['month'] < 10) {
					var monthString = '0' + dayContainers[key]['date']['month'].toString();
				} else {
					var monthString = dayContainers[key]['date']['month'].toString();
				}
				if (dayContainers[key]['date']['day'] < 10) {
					var dayString = '0' + dayContainers[key]['date']['day'].toString();
				} else {
					var dayString = dayContainers[key]['date']['day'].toString();
				}
				let dateKey = dayContainers[key]['date']['year'].toString() + monthString + dayString;
				return(
					<DayContainer dayData={dayContainers[key]} userData={userData} key={dateKey} adminFunctions={admin} />
				)
			}
		})


		return(
			<div id="appContainer">
				<HomeTop user={this.state.user} />
				<section id="homePagePosts">
					<Leader clipdata={this.state.winner} autoplay={false} adminFunctions={admin} />
					{dayContainerComponents}
				</section>
				{this.state.loadingMore ? <div className="lds-ring"><div></div><div></div><div></div><div></div></div> : ""}
			</div>
		)
	}
}

if (jQuery('#homepageApp').length) {
	ReactDOM.render(
		<Homepage />,
		document.getElementById('homepageApp')
	);
}