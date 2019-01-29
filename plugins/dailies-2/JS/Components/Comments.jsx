import React from "react";
import Comment from './Comment.jsx';

export default class Comments extends React.Component{
	constructor() {
		super();
		this.state = {
			commentAnonymously: false,
		}
		this.postCommentHandler = this.postCommentHandler.bind(this);
	}

	componentDidMount() {
		let postCommentHandler = this.postCommentHandler;
		jQuery(`#commentBox-${this.props.slug}`).keypress(function(e) {
			if(e.which == 13 && !e.shiftKey) {
				postCommentHandler(e);
			}
		});
	}

	componentDidUpdate() {
		let postCommentHandler = this.postCommentHandler;
		let commentBox = jQuery(`#commentBox-${this.props.slug}`);
		commentBox.off();
		commentBox.keypress(function(e) {
			if(e.which == 13 && !e.shiftKey) {
				postCommentHandler(e);
			}
		});
	}

	postCommentHandler(e) {
		e.preventDefault();
		let comment = e.target.value;
		e.target.value = '';
		let commentObject = {
			comment,
			replytoid: null,
			anonymous: this.state.commentAnonymously,
		}
		console.log(commentObject);
		this.props.postComment(commentObject);
	}

	toggleAnonymousComment() {
		this.setState({commentAnonymously: !this.state.commentAnonymously});
	}

	render() {
		let commentsLoader;
		let comments;
		let boundThis = this;
		if (this.props.commentsLoading == true) {
			commentsLoader = <div className="lds-ring"><div></div><div></div><div></div><div></div></div>;
		} else {
			commentsLoader = "";
		}
		if (this.props.comments.length == 0 && this.props.commentsLoading === false) {
			comments = <div className="comment">No Comments yet</div>;
		} else {
			comments = this.props.comments.map( function(commentData, index) {
				return <Comment key={commentData.id} commentID={commentData.id} commenter={commentData.commenter} pic={commentData.pic} commentTime={commentData.time} comment={commentData.comment} score={commentData.score} yeaComment={boundThis.props.yeaComment} delComment={boundThis.props.delComment} />;
			});
		}

		let commentForm;
		let redirectURI = window.location.href.replace(/\//g, "%2F").replace(":", "%3A");
		if (dailiesGlobalData.userData.userID === 0 && !this.state.commentAnonymously) {
			commentForm =
				<div id="wp-social-login" class="">
					<style type="text/css">
					.wp-social-login-connect-with{}.wp-social-login-provider-list{}.wp-social-login-provider-list a{}.wp-social-login-provider-list img{}.wsl_connect_with_provider{}</style>
					<div class="wp-social-login-widget">
						<div class="wp-social-login-connect-with">Please log in to comment:</div>
						<div class="wp-social-login-provider-list">
							<a rel="nofollow" href={`https://dailies.gg/wp-login.php?action=wordpress_social_authenticate&amp;mode=login&amp;provider=Facebook&amp;redirect_to=${redirectURI}`} title="Connect with Facebook" class="wp-social-login-provider wp-social-login-provider-facebook" data-provider="Facebook">
								Facebook
							</a>
							<a rel="nofollow" href={`https://dailies.gg/wp-login.php?action=wordpress_social_authenticate&amp;mode=login&amp;provider=Google&amp;redirect_to=${redirectURI}`} title="Connect with Google" class="wp-social-login-provider wp-social-login-provider-google" data-provider="Google">
								Google
							</a>
							<a rel="nofollow" href={`https://dailies.gg/wp-login.php?action=wordpress_social_authenticate&amp;mode=login&amp;provider=Twitter&amp;redirect_to=${redirectURI}`} title="Connect with Twitter" class="wp-social-login-provider wp-social-login-provider-twitter" data-provider="Twitter">
								Twitter
							</a>
							<a rel="nofollow" href={`https://dailies.gg/wp-login.php?action=wordpress_social_authenticate&amp;mode=login&amp;provider=Steam&amp;redirect_to=${redirectURI}`} title="Connect with Steam" class="wp-social-login-provider wp-social-login-provider-steam" data-provider="Steam">
								Steam
							</a>
							<a rel="nofollow" href={`https://dailies.gg/wp-login.php?action=wordpress_social_authenticate&amp;mode=login&amp;provider=TwitchTV&amp;redirect_to=${redirectURI}`} title="Connect with Twitch.tv" class="wp-social-login-provider wp-social-login-provider-twitchtv" data-provider="TwitchTV">
								Twitch.tv
							</a>
						</div>
						<div class="wp-social-login-widget-clearing"></div>
					</div>
				</div>
			;
		} else {
			commentForm = <textarea id={`commentBox-${this.props.slug}`} className="commentBox" name="commentBox" placeholder="Add Comment" minLength="1" maxLength="2200" spellCheck="true" rows="1" onSubmit={this.commentHandler}/>;
		}
		return(
			<div id={`comments-${this.props.slug}`} className="comments">
				{comments}
				<div id={`commentsLoader-${this.props.slug}`} className="commentsLoader">{commentsLoader}</div>
				{commentForm}
				<div className="anonymousCommentForm">
					<input type="checkbox" checked={this.state.commentAnonymously} className="commentAnonymouslyCheckbox" name="commentAnonymouslyCheckbox" onChange={() => this.toggleAnonymousComment()} />Comment anonymously
				</div>
			</div>
		)
	}

}