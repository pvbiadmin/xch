<?php

namespace BPL\Mods\Terms;

function main(): string
{
    return <<<HTML
    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <h5 class="text-center mb-4">
                        <span class="fs-4">Agreement on Terms and Conditions</span><br>
                        <span class="fs-6">(IMPORTANT – PLEASE READ CAREFULLY)</span>
                    </h5>

                    <h6>1. Participation Amount</h6>
                    <p>
                        a. The minimum required participation amount is <strong>Five Thousand Pesos (PHP 5,000.00)</strong>.<br>
                        b. The maximum allowable participation per individual or entity is <strong>Five Hundred Thousand Pesos (PHP 500,000.00)</strong>.<br>
                        c. Any attempt to exceed the maximum limit will be subject to review and may be declined.
                    </p>

                    <h6>2. Service Period and Earnings</h6>
                    <p>
                        a. The service is subject to a <strong>daily percentage-based return of 1%</strong>.<br>
                        b. The service period lasts for <strong>Ninety (90) days or Three (3) months</strong> from the start date.
                    </p>

                    <h6>3. Payout and Processing Schedule</h6>
                    <p>
                        a. Requests for withdrawal must be submitted <strong>every Monday of each week</strong>.<br>
                        b. Approved requests shall be processed and released <strong>every Wednesday of the same week</strong>.<br>
                        c. Disbursements will follow a <strong>ten-day cycle</strong>, subject to processing timelines.
                    </p>

                    <h6>4. Early Withdrawal Terms</h6>
                    <p>Requests for early withdrawal before the completion of the service period will incur a <strong>service charge of 80%</strong> of the remaining amount.</p>

                    <h6>5. Renewal and Continuation</h6>
                    <p>Participants may opt to renew or continue their participation upon completion of the service period, subject to updated terms.</p>

                    <h6>6. Compliance with Regulations</h6>
                    <p>All terms and conditions are governed by the laws of the <strong>Republic of the Philippines</strong>.</p>

                    <h6>7. Limitation of Liability</h6>
                    <p>
                        a. In the event of unforeseen business circumstances, operational limitations, or force majeure, responsibilities will not be transferred to another party.<br>
                        b. The participant acknowledges that any revenue-sharing arrangement or service participation involves operational risks.
                    </p>

                    <h6>8. Adjustments and Modifications</h6>
                    <p>We reserve the right to modify, update, or revise these terms at any time. Continued participation constitutes acceptance of the revised terms.</p>

                    <h6>9. Privacy Policy</h6>
                    <p>We are committed to maintaining confidentiality. Personal information will not be shared with third parties except as required by law.</p>

                    <h6>10. Acknowledgment</h6>
                    <p>By agreeing to these terms, the participant acknowledges full understanding of the policies and conditions outlined.</p>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Agree</button>
                </div>
            </div>
        </div>
    </div>
    HTML;
}
