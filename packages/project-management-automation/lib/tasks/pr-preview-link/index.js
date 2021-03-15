// todo update everything

/**
 * Internal dependencies
 */
const debug = require( '../../debug' );

/** @typedef {import('@actions/github').GitHub} GitHub */
/** @typedef {import('@octokit/webhooks').WebhookPayloadPullRequest} WebhookPayloadPullRequest */

/**
 * Assigns the first-time contributor label to PRs.
 *
 * @param {WebhookPayloadPullRequest} payload Pull request event payload.
 * @param {GitHub}                    octokit Initialized Octokit REST client.
 */
async function prPreviewLink( payload, octokit ) {
	const repo = payload.repository.name;
	const owner = payload.repository.owner.login;

	//const { data: commits } = await octokit.repos.listCommits( {
	//	owner,
	//	repo,
	//	author,
	//} );

	// if this is a push, return (not during testing though)
	// already done by the way it's regstered? test by pushing addition commits to branch

	//if ( false ) {
	//	debug(
	//		`first-time-contributor-label: Not the first commit for author. Aborting`
	//	);
	//
	//	return;
	//}

	const pullRequestNumber = payload.pull_request.number;

	debug(
		`pr-preview-link: hello world! pr # is: ${ pullRequestNumber }`
	);

	//await octokit.issues.addLabels( {
	//	owner,
	//	repo,
	//	issue_number: payload.pull_request.number,
	//	labels: [ 'First-time Contributor' ],
	//} );

	/**
	 * Adds a welcome comment to the first time PR
	 */

	await octokit.issues.createComment( {
		owner,
		repo,
		issue_number: payload.pull_request.number,
		// replace ^ w/ pullRequestNumber
		body:
			'Preview site for this PR: http://gutenberg.run/' +
			pullRequestNumber +
			' - ' +
			payload.pull_request.number,// just in case
	} );
}

module.exports = prPreviewLink;
