/**
 * Use data-store to get list of validated blocks.
 * Report invalid blocks with their validation problems.
 */

import { logInteractionEvent } from './instrumentation';
import { serialize } from '@wordpress/blocks'

(function(wp) {
    const subscribe = wp?.data?.subscribe;
    const select = wp?.data?.select;
    const dispatch = wp?.data?.dispatch;
    let isChecked = false;

    // Bail if missing anything.
    if (!subscribe || !select || !dispatch || !wp) {
        return;
    }  

    if ( typeof logInteractionEvent !== 'function' || typeof serialize !== 'function') {
        return;
    }

    function findInvalidBlocks(blocks) {
        let invalidBlocks = [];
        blocks.forEach((block) => {
            if (block.isValid === false) {
                invalidBlocks.push({
                    name: block.name,
                    validationIssues: block.validationIssues,
                    serializedBlock: serialize(block),
                });
            } else {
                if (block.innerBlocks && block.innerBlocks.length > 0) {
                    invalidBlocks = invalidBlocks.concat(findInvalidBlocks(block.innerBlocks));
                }
            }
        });
        return invalidBlocks;
    }

    function checkBlockForValidity(){
        let blocks = select('core/block-editor').getBlocks();
        let invalidBlocks = findInvalidBlocks(blocks);

        if (invalidBlocks.length > 0) {
            logInteractionEvent({eid: 'block-validation-failed', type: 'event', data: invalidBlocks});
        }
        isChecked = true;
    }

    let unsubscribe = subscribe(function() {
        let isEditorReady = select('core/editor')?.isCleanNewPost() !== null;
        let hasBlocks = select('core/block-editor')?.getBlockCount() > 0;

        if (isEditorReady && hasBlocks) {
            if (!isChecked){
                unsubscribe();
            }
            setTimeout(checkBlockForValidity(), 2000);
        }
    });

})(window?.wp);