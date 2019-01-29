import React from "react";
import ReactDOM from 'react-dom';
import Comments from './Comments.jsx';

export default class MultipleWinnersDiscussion extends React.Component{
	constructor() {
		super();

		this.state = {
			comments: multipleWinnersComments,
			commentsLoading: false,
		}

		this.postComment = this.postComment.bind(this);
		this.yeaComment = this.yeaComment.bind(this);
		this.delComment = this.delComment.bind(this);
	}

	postComment(commentObject) {
		let currentState = this.state;
		currentState.commentsLoading = true;
		this.setState(currentState);
		// let randomID = Math.round(Math.random() * 100);
		let boundThis = this;
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				slug: "multipleWinnersDiscussion",
				commentObject,
				action: 'post_comment',
			},
			error: function(one, two, three) {
				console.log(one);
				console.log(two);
				console.log(three);
			},
			success: function(data) {
				// jQuery.each(currentState.comments, function(index,commentData) {
				// 	if (commentData.id == randomID) {
				// 		currentState.comments[index].id = data;
				// 	}
				// });
				// this.setState(currentState);
				let commentData = {
					comment: commentObject.comment,
					commenter: commentObject.anonymous ? "Anon" : dailiesGlobalData.userData.userName,
					pic: commentObject.anonymous ? "https://dailies.gg/wp-content/uploads/2017/03/default_pic.jpg" : dailiesGlobalData.userData.userPic,
					id: data,
					replytoid: commentObject.replytoid,
					slug: "multipleWinnersDiscussion",
					score: 0,
					time: Date.now(),
				}
				currentState.comments.push(commentData);
				currentState.commentsLoading = false;
				boundThis.setState(currentState);
			}
		});
	}

	yeaComment(commentID) {
		let currentState = this.state;
		jQuery.each(currentState.comments, function(index, data) {
			if (data.id == commentID) {
				currentState.comments[index].score = Number(data.score) + 1;
			}
		})
		this.setState(currentState);
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				commentID,
				action: 'yea_comment',
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

	delComment(commentID) {
		let currentState = this.state;
		jQuery.each(currentState.comments, function(index, commentData) {
			if (commentData === undefined) {return true;}
			if (commentID == commentData.id) {
				delete currentState.comments[index];
			}
		});
		this.setState(currentState);
		jQuery.ajax({
			type: "POST",
			url: dailiesGlobalData.ajaxurl,
			dataType: 'json',
			data: {
				commentID,
				action: 'del_comment',
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

	render() {
		return(
			<section id="MultipleWinnersComments">
				<h4 className="MultipleWinnersCommentsHeader">Comments</h4>
				<Comments key={`comments-multipleWinnersDiscussion`} slug="multipleWinnersDiscussion" postComment={this.postComment} commentsLoading={this.state.commentsLoading} comments={this.state.comments} yeaComment={this.yeaComment} delComment={this.delComment} />
			</section>
		)
	}
}

ReactDOM.render(
	<MultipleWinnersDiscussion />,
	document.getElementById('multipleWinnersDiscussion')
);