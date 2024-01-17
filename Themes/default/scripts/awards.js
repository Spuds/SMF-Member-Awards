// Had to make another file for this, because the PM one wants a BCC field
// This is just a stripped-down version of PersonalMessage.js

// Handle the JavaScript surrounding awards send form.
function smf_AwardSend(oOptions)
{
	this.opt = oOptions;
	this.oToAutoSuggest = null;
	this.oToListContainer = null;
	this.init();
}

smf_AwardSend.prototype.init = function()
{
	var oToControl = document.getElementById(this.opt.sToControlId);
	this.oToAutoSuggest = new smc_AutoSuggest({
		sSelf: this.opt.sSelf + '.oToAutoSuggest',
		sSessionId: this.opt.sSessionId,
		sSessionVar: this.opt.sSessionVar,
		sSuggestId: 'to_suggest',
		sControlId: this.opt.sToControlId,
		sSearchType: 'member',
		sPostName: 'recipient_to',
		iMinimumSearchChars: 2,
		sURLMask: 'action=profile;u=%item_id%',
		sTextDeleteItem: this.opt.sTextDeleteItem,
		bItemList: true,
		sItemListContainerId: 'to_item_list_container',
		aListItems: this.opt.aToRecipients
	});
	this.oToAutoSuggest.registerCallback('onBeforeAddItem', this.opt.sSelf + '.callbackAddItem');
};

// Prevent items to be added twice or to both the 'To'.
smf_AwardSend.prototype.callbackAddItem = function(oAutoSuggestInstance, sSuggestId)
{
	this.oToAutoSuggest.deleteAddedItem(sSuggestId);
	return true;
};
