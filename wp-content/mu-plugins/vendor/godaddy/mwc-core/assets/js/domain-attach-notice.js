/* global gdDomainAttachNotice */

document.addEventListener('DOMContentLoaded', () => {
	const siteAddressField = document.getElementById('home');
	if (siteAddressField && gdDomainAttachNotice.siteAddressText) {
		siteAddressField.after(buildNoticeHtml(gdDomainAttachNotice.siteAddressText));
	}

	const seoRowWrapper = document.querySelector('.option-site-visibility .description');
	if(seoRowWrapper && gdDomainAttachNotice.seoVisibilityText) {
		seoRowWrapper.innerHTML = gdDomainAttachNotice.seoVisibilityText;
	}

	function buildNoticeHtml(noticeText)
	{
		const noticeWrapper = document.createElement('p');
		noticeWrapper.classList.add('description');
		noticeWrapper.innerHTML = noticeText;

		return noticeWrapper;
	}
});
