import React from "react";

const ContenderVoteBar = ({voteData}) => {
	let totalscore = 0;
	voteData.forEach((data) => totalscore += data);
	// totalscore = 0;
	if (totalscore <= 0) {
		return <div className="ContenderVoteBar">No votes yet!</div>
	}
	let counter = 0;
	let votebars = voteData.map( (votedata) => {
		counter++;
		let widthPercentage = votedata / totalscore * 100;
		let style = {
			width: `${widthPercentage}%`,
		}
		return <aside className={`contenderVoteSection ${counter}`} key={counter} style={style}>{counter}<span className="score">{votedata}</span></aside>
	});
	return <div className="ContenderVoteBar">{votebars}</div>
}

export default ContenderVoteBar;