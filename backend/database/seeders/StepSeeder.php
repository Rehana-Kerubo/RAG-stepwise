<?php

namespace Database\Seeders;

use App\Models\Gig;
use App\Models\Step;
use Illuminate\Database\Seeder;

class StepSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGig([
            'slug'        => 'pos-merchant-onboarding',
            'name'        => 'POS Merchant Onboarding',
            'client'      => 'Safaricom M-PESA / POS',
            'description' => 'Onboard an SME merchant onto a POS device and an M-PESA Till or Paybill.',
        ], $this->posSteps());
    }

    // Updates in place (matched on gig + step number) so ids and saved progress survive a re-seed.
    private function seedGig(array $gigData, array $steps): void
    {
        $gig = Gig::updateOrCreate(['slug' => $gigData['slug']], $gigData);

        foreach ($steps as $step) {
            Step::updateOrCreate(
                ['gig_id' => $gig->id, 'order_number' => $step['order_number']],
                $step
            );
        }
    }

    private function photo(string $label, string $description, string $capture): array
    {
        return [
            'input_type'   => 'photo',
            'input_config' => [
                'submission_type' => 'document',
                'label'           => $label,
                'description'     => $description,
                'capture'         => $capture,
            ],
        ];
    }

    private function confirm(string $label, string $statement): array
    {
        return [
            'input_type'   => 'confirm',
            'input_config' => [
                'submission_type' => 'confirmation',
                'label'           => $label,
                'statement'       => $statement,
            ],
        ];
    }

    private function posSteps(): array
    {
        return [
            [
                'title' => 'Collect Customer ID',
                'instructions' => 'Collect the owner\'s national ID or passport. Photograph the document directly through the agent app camera.',
                'order_number' => 1,
                'validation_rules' => 'A clear national ID or passport image must be provided.',
                'guidance_label' => 'Capture the customer\'s identification document',
            ] + $this->photo('Customer ID or passport', 'Capture a clear image of the customer’s national ID or passport.', 'environment'),

            [
                'title' => 'Capture Customer Photo',
                'instructions' => 'Take a clear passport-size photo of the customer using the agent app camera.',
                'order_number' => 2,
                'validation_rules' => 'A clear customer photo must be provided.',
                'guidance_label' => 'Capture the customer\'s passport-size photo',
            ] + $this->photo('Customer photo', 'Take a clear passport-size photo of the customer.', 'user'),

            [
                'title' => 'Capture Proof of Business Location',
                'instructions' => 'Capture proof of the customer\'s business location using the agent app camera.',
                'order_number' => 3,
                'validation_rules' => 'Proof of business location must be provided and legible.',
                'guidance_label' => 'Capture proof of the business location',
            ] + $this->photo('Proof of business location', 'Capture the business permit or utility bill showing the business location.', 'environment'),

            [
                'title' => 'Submit KYC Documents',
                'instructions' => 'Review the collected identification document, customer photo, and proof of business location, then submit them for verification.',
                'order_number' => 4,
                'validation_rules' => 'All required KYC documents must be present and legible.',
                'guidance_label' => 'Review and submit the KYC documents',
            ] + $this->confirm('KYC submission', 'I have reviewed the required KYC documents and submitted them for verification.'),

            [
                'title' => 'Power On POS Device',
                'instructions' => 'Power on the POS device and confirm that the device starts successfully.',
                'order_number' => 5,
                'validation_rules' => 'The POS device must be powered on successfully.',
                'guidance_label' => 'Power on and prepare the POS device',
            ] + $this->confirm('POS device', 'I have powered on the POS device and confirmed that it starts successfully.'),

            [
                'title' => 'Connect POS Device',
                'instructions' => 'Connect the POS device to Wi-Fi or insert a SIM card to establish connectivity.',
                'order_number' => 6,
                'validation_rules' => 'The POS device must have an active network connection.',
                'guidance_label' => 'Connect the POS device to a network',
            ] + $this->confirm('Network connection', 'I have connected the POS device to Wi-Fi or mobile data and confirmed connectivity.'),

            [
                'title' => 'Enter POS Activation Code',
                'instructions' => 'Enter the activation code sent to the agent dashboard into the POS device.',
                'order_number' => 7,
                'validation_rules' => 'The activation code must be entered successfully.',
                'guidance_label' => 'Activate the POS device using the provided code',
                'input_type' => 'text',
                'input_config' => [
                    'submission_type' => 'activation_code',
                    'label'           => 'POS activation code',
                    'placeholder'     => 'Enter activation code',
                    'max_length'      => 100,
                ],
            ],

            [
                'title' => 'Perform POS Test Transaction',
                'instructions' => 'Run a KES 1 test transaction to confirm that the POS device is configured correctly and can sync successfully.',
                'order_number' => 8,
                'validation_rules' => 'The test transaction must appear on the merchant dashboard within 2 minutes.',
                'guidance_label' => 'Perform a KES 1 test transaction',
            ] + $this->confirm('Test transaction', 'I performed the KES 1 test transaction and confirmed it appeared on the merchant dashboard within 2 minutes.'),

            [
                'title' => 'Submit Till or Paybill Request',
                'instructions' => 'Submit the business registration certificate and ID to request a Till number. For a Paybill number, confirm that the merchant has an active KRA PIN first.',
                'order_number' => 9,
                'validation_rules' => 'Required business documents must be submitted and the applicable requirements confirmed.',
                'guidance_label' => 'Submit the required information for Till or Paybill linking',
                'input_type' => 'select',
                'input_config' => [
                    'submission_type'   => 'linking_request',
                    'label'             => 'Till or Paybill request',
                    'field'             => 'request_type',
                    'select_label'      => 'Request type',
                    'placeholder'       => 'Choose request type',
                    'options'           => [
                        ['value' => 'Till',    'label' => 'Till'],
                        ['value' => 'Paybill', 'label' => 'Paybill'],
                    ],
                    'confirm_statement' => 'I have checked the applicable requirements and submitted the request.',
                    'response_text'     => 'Request requirements checked and request submitted',
                ],
            ],

            [
                'title' => 'Confirm Till or Paybill',
                'instructions' => 'Confirm that the merchant has received the SMS confirmation for the Till or Paybill request.',
                'order_number' => 10,
                'validation_rules' => 'SMS confirmation must be received before completing this step.',
                'guidance_label' => 'Confirm the Till or Paybill activation',
            ] + $this->confirm('Activation confirmation', 'I have confirmed that the merchant received the SMS confirming Till or Paybill activation.'),

            [
                'title' => 'Perform First Transaction',
                'instructions' => 'Guide the merchant through a small live transaction, such as KES 50, using Card or Mobile Money.',
                'order_number' => 11,
                'validation_rules' => 'The transaction must complete successfully.',
                'guidance_label' => 'Guide the merchant through their first transaction',
            ] + $this->confirm('First transaction', 'I guided the merchant through a transaction and confirmed it completed successfully.'),

            [
                'title' => 'Check Transaction History',
                'instructions' => 'Show the merchant how to check their balance and view their transaction history.',
                'order_number' => 12,
                'validation_rules' => 'The merchant must be shown where to view balance and transaction history.',
                'guidance_label' => 'Show the merchant how to view transactions',
            ] + $this->confirm('Transaction history', 'I showed the merchant how to view their balance and transaction history.'),

            [
                'title' => 'Set Up Staff Accounts',
                'instructions' => 'If the merchant has employees who will use the device, create individual staff accounts under Settings > Staff Management with limited permissions.',
                'order_number' => 13,
                'validation_rules' => 'This step is optional and is only required if the merchant has staff beyond the owner.',
                'guidance_label' => 'Set up staff accounts if applicable',
                'input_type' => 'select',
                'input_config' => [
                    'submission_type' => 'staff_accounts',
                    'label'           => 'Staff account setup',
                    'description'     => 'Does the merchant have employees who need individual accounts?',
                    'field'           => 'response',
                    'placeholder'     => 'Choose an option',
                    'options'         => [
                        ['value' => 'setup_required',  'label' => 'Yes — staff accounts have been set up'],
                        ['value' => 'not_applicable',  'label' => 'No additional staff accounts are needed'],
                    ],
                ],
            ],
        ];
    }
}