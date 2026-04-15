<?php

namespace Database\Seeders;

use App\Models\TermsLibrary;
use Illuminate\Database\Seeder;

class TermsLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $terms = [
            "Progress payments become due upon completion of established milestones, with the final balance due upon successful delivery of all tasks outlined in 'Scope of Work' (SoW). Any deliverables and/or assets due will be transferred to Client upon receipt of the final balance.",
            "divStrong will only use materials that are in accordance with copyright laws and Client agrees to only provide material to divStrong for which they have approval, license and/or permission.",
            "divStrong shall not be held liable for the accuracy of any information provided by Client.",
            "divStrong requires approval of SoW by Client before services will be rendered. Modifications or revisions to the SoW following approval will be billed at the established hourly rate. Client shall receive notice of, and must approve any change requests in order for them to proceed as additional work product.",
            "If at any point Client becomes unhappy with the level of service or quality of work received, Client may cancel the project in accordance with the termination clause (#8). A pro-rated refund may be issued for any uncompleted work. 'Uncompleted Work' is defined as any work product specified in the SoW including any subsequently approved change requests that have not been substantially completed or submitted for Client review.",
            "In the event that any invoice becomes past due for 30 days or more, divStrong reserves the right to stop work without any further communication.",
            "divStrong will not provide Client's confidential information to any third parties - including, but not limited to names, addresses and trade secrets - without prior authorization from Client. divStrong agrees to take reasonable precautions to prevent unauthorized disclosure of any confidential information provided by Client.",
            "This Agreement is effective as of the Effective Date noted herein and shall continue unless terminated; divStrong or Client may terminate this agreement by submitting written notice outlining the reasons for termination. The receiving party will have five (5) days to address the breach and if no resolution is agreed upon will result in the termination of this Agreement. Client will pay divStrong for all services rendered prior to termination.",
            "Client agrees not to circumvent divStrong to work independently with divStrong employees, partners, vendors or subcontractors which are only known to client by virtue of the relationship with divStrong, except as expressly agreed to in writing between Client and divStrong.",
        ];

        foreach ($terms as $i => $content) {
            TermsLibrary::updateOrCreate(
                ['sort_order' => $i + 1],
                ['content' => $content, 'is_active' => true],
            );
        }
    }
}
