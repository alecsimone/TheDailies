import React from "react";
import ClipPlayer from "../ClipPlayer.jsx";

export default class ClickToPlayThumb extends React.Component {
	constructor() {
		super();
		this.state = {
			embedding: false,
		}
	}

	embedClip() {
		this.setState({embedding: true});
	}

	render() {
		let thumbSrc = this.props.clipdata.thumb;
		if (thumbSrc === 'none' || thumbSrc === '') {
			thumbSrc = `${dailiesGlobalData.thisDomain}/wp-content/uploads/2018/09/default-clip-thumb.jpg`;
		}
		let thumb = [];
		thumb.push(<img key={`${this.props.clipdata.slug}-thumb`} src={thumbSrc} className="topfivethumb" onClick={() => this.embedClip()} />);
		thumb.push(<img key={`${this.props.clipdata.slug}-playbutton`} src="https://dailies.gg/wp-content/uploads/2016/08/playbutton.png" className="playbutton" />);

		let thumbSlotContents;
		if (this.state.embedding) {
			thumbSlotContents = <ClipPlayer type={this.props.clipdata.type} slug={this.props.clipdata.slug} vodlink={this.props.clipdata.vodlink} autoplay={true}/>;
		} else {
			thumbSlotContents = thumb;	
		}
		return(
			<div className="clickToPlayThumb">
				{thumbSlotContents}
			</div>
		)
	}

}