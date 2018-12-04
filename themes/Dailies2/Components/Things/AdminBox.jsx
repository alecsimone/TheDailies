import React from "react";
import KeepBar from "../KeepBar.jsx";

const AdminBox = ({adminFunctions, identifier}) => {
	// console.log(identifier);
	if (!adminFunctions || (dailiesGlobalData.userData.userRole !== "administrator" && dailiesGlobalData.userData.userRole !== "editor")) {
		return <div />
	}
	let cut;
	if (adminFunctions.cut) {
		cut = <img src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2017/04/red-x.png`} className="cutButton" onClick={() => adminFunctions.cut(identifier)} />;
	}
	let keep;
	if (adminFunctions.keep) {
		keep = <KeepBar slug={identifier} keepSlug={adminFunctions.keep} />;
	}
	let input;
	if (adminFunctions.input) {
		input = 
			<form className="adminInput" onSubmit={(e) => inputSubmit(e)}>
				<input type="text" placeholder="+Score" className="addScoreBox" />
			</form>
		let inputSubmit = function(e) {
			e.preventDefault();
			let input = e.target.children[0];
			adminFunctions.input(input.value, identifier);
			input.value = '';
		}
	}
	let promote;
	if (adminFunctions.promote) {
		promote = <img src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2018/01/Green-Up-Arrow.png`} className="promotePostButton" onClick={() => adminFunctions.promote(identifier)}/>;
	}
	let edit;
	if (adminFunctions.edit) {
		edit = <a href={`${dailiesGlobalData.thisDomain}/wp-admin/post.php?post=${identifier}&action=edit`} className="editPostButton" target="_blank"><img src={`${dailiesGlobalData.thisDomain}/wp-content/uploads/2017/07/edit-this.png`} className="editThisImg" /></a>

	}
	let checkBox;
	if (adminFunctions.toggle) {
		checkBox = <input type="checkbox" className="checkbox" onClick={(e) => adminFunctions.toggle(e, identifier)} />
	}
	return(
		<div className="AdminBox">
			<div className="AdminLeft">
				{cut}
			</div>
			<div className="AdminMiddle">
				{keep}{input}
			</div>
			<div className="AdminRight">
				{checkBox} {promote} {edit}
			</div>
		</div>
	);
};

export default AdminBox;