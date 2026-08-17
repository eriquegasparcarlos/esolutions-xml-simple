@php echo '<?xml version="1.0" encoding="utf-8" standalone="no"?>'; @endphp
<SummaryDocuments
        xmlns="urn:sunat:names:specification:ubl:peru:schema:xsd:SummaryDocuments-1"
        xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
        xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
        xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
        xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
        xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent/>
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.0</cbc:UBLVersionID>
    <cbc:CustomizationID>1.1</cbc:CustomizationID>
    <cbc:ID>{{ $document['identifier'] }}</cbc:ID>
    <cbc:ReferenceDate>{{ $document['date_of_reference'] }}</cbc:ReferenceDate>
    <cbc:IssueDate>{{ $document['date_of_issue'] }}</cbc:IssueDate>
    <cac:Signature>
        <cbc:ID>{{ $document['signature_uri'] }}</cbc:ID>
        <cbc:Note>{{ $document['signature_note'] }}</cbc:Note>
        <cac:SignatoryParty>
            <cac:PartyIdentification>
                <cbc:ID>{{ $document['company_number'] }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[{{ $document['company_name'] }}]]></cbc:Name>
            </cac:PartyName>
        </cac:SignatoryParty>
        <cac:DigitalSignatureAttachment>
            <cac:ExternalReference>
                <cbc:URI>#{{ $document['signature_uri'] }}</cbc:URI>
            </cac:ExternalReference>
        </cac:DigitalSignatureAttachment>
    </cac:Signature>
    <cac:AccountingSupplierParty>
        <cbc:CustomerAssignedAccountID>{{ $document['company_number'] }}</cbc:CustomerAssignedAccountID>
        <cbc:AdditionalAccountID>6</cbc:AdditionalAccountID>
        <cac:Party>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[{{ $document['company_name'] }}]]></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>
    @foreach($document['documents'] as $row)
    <sac:SummaryDocumentsLine>
        <cbc:LineID>{{ $loop->iteration }}</cbc:LineID>
        <cbc:DocumentTypeCode>{{ $row['document_type_id'] }}</cbc:DocumentTypeCode>
        <cbc:ID>{{ $row['id'] }}</cbc:ID>
        <cac:AccountingCustomerParty>
            <cbc:CustomerAssignedAccountID>{{ $row['customer_number'] }}</cbc:CustomerAssignedAccountID>
            <cbc:AdditionalAccountID>{{ $row['customer_identity_document_type_id'] }}</cbc:AdditionalAccountID>
            @if(($document['date_of_issue'] ?? '') >= config('esolutions_xml_simple.rule_dates.summary_2026', '2027-01-01') && !empty($row['customer_name']))
                {{-- #29 (vigencia 2027-01-01): apellidos/nombres o razón social del adquirente --}}
                <cac:Party>
                    <cac:PartyLegalEntity>
                        <cbc:RegistrationName><![CDATA[{{ $row['customer_name'] }}]]></cbc:RegistrationName>
                    </cac:PartyLegalEntity>
                </cac:Party>
            @endif
        </cac:AccountingCustomerParty>
        @if(in_array($row['document_type_id'], ['07', '08']) && !empty($row['affected_document']))
        <cac:BillingReference>
            <cac:InvoiceDocumentReference>
                <cbc:ID>{{ $row['affected_document']['id'] }}</cbc:ID>
                <cbc:DocumentTypeCode>{{ $row['affected_document']['document_type_id'] }}</cbc:DocumentTypeCode>
            </cac:InvoiceDocumentReference>
        </cac:BillingReference>
        @endif
        {{-- Catálogo 19: 1=adicionar, 2=modificar, 3=anular --}}
        <cac:Status>
            <cbc:ConditionCode>{{ $row['status_id'] }}</cbc:ConditionCode>
        </cac:Status>
        <sac:TotalAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total'] }}</sac:TotalAmount>
        @if(floatval($row['total_taxed']) > 0)
        <sac:BillingPayment>
            <cbc:PaidAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_taxed'] }}</cbc:PaidAmount>
            <cbc:InstructionID>01</cbc:InstructionID>
        </sac:BillingPayment>
        @endif
        @if(floatval($row['total_exonerated']) > 0)
        <sac:BillingPayment>
            <cbc:PaidAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_exonerated'] }}</cbc:PaidAmount>
            <cbc:InstructionID>02</cbc:InstructionID>
        </sac:BillingPayment>
        @endif
        @if(floatval($row['total_unaffected']) > 0)
        <sac:BillingPayment>
            <cbc:PaidAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_unaffected'] }}</cbc:PaidAmount>
            <cbc:InstructionID>03</cbc:InstructionID>
        </sac:BillingPayment>
        @endif
        @if(floatval($row['total_exportation']) > 0)
        <sac:BillingPayment>
            <cbc:PaidAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_exportation'] }}</cbc:PaidAmount>
            <cbc:InstructionID>04</cbc:InstructionID>
        </sac:BillingPayment>
        @endif
        @if(($document['date_of_issue'] ?? '') >= config('esolutions_xml_simple.rule_dates.summary_2026', '2027-01-01'))
            {{-- #26 (vigencia 2027-01-01): gratuitas desagregadas 06 gravadas, 07
                 exoneradas, 08 inafectas, 09 exportación (reemplaza el 05) --}}
            @foreach(['total_free_taxed' => '06', 'total_free_exonerated' => '07', 'total_free_unaffected' => '08', 'total_free_exportation' => '09'] as $freeKey => $instr)
                @if(floatval($row[$freeKey] ?? 0) > 0)
                <sac:BillingPayment>
                    <cbc:PaidAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row[$freeKey] }}</cbc:PaidAmount>
                    <cbc:InstructionID>{{ $instr }}</cbc:InstructionID>
                </sac:BillingPayment>
                @endif
            @endforeach
        @else
            @if(floatval($row['total_free']) > 0)
            <sac:BillingPayment>
                <cbc:PaidAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_free'] }}</cbc:PaidAmount>
                <cbc:InstructionID>05</cbc:InstructionID>
            </sac:BillingPayment>
            @endif
        @endif
        @if(floatval($row['total_charge']) > 0)
        <cac:AllowanceCharge>
            <cbc:ChargeIndicator>true</cbc:ChargeIndicator>
            <cbc:Amount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_charge'] }}</cbc:Amount>
        </cac:AllowanceCharge>
        @endif
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_igv'] }}</cbc:TaxAmount>
            <cac:TaxSubtotal>
                <cbc:TaxAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_igv'] }}</cbc:TaxAmount>
                <cac:TaxCategory>
                    @if(($document['date_of_issue'] ?? '') >= config('esolutions_xml_simple.rule_dates.summary_2026', '2027-01-01'))
                        {{-- #27 (vigencia 2027-01-01): Tasa del IGV --}}
                        <cbc:Percent>{{ $row['igv_percent'] ?? 18 }}</cbc:Percent>
                    @endif
                    <cac:TaxScheme>
                        <cbc:ID>1000</cbc:ID>
                        <cbc:Name>IGV</cbc:Name>
                        <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
        @if(($document['date_of_issue'] ?? '') >= config('esolutions_xml_simple.rule_dates.summary_2026', '2027-01-01') && floatval($row['total_igv_free'] ?? 0) > 0)
        {{-- #28 (vigencia 2027-01-01): IGV de operaciones gratuitas --}}
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_igv_free'] }}</cbc:TaxAmount>
            <cac:TaxSubtotal>
                <cbc:TaxAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_igv_free'] }}</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9996</cbc:ID>
                        <cbc:Name>GRA</cbc:Name>
                        <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
        @endif
        @if(floatval($row['total_isc']) > 0)
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_isc'] }}</cbc:TaxAmount>
            <cac:TaxSubtotal>
                <cbc:TaxAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_isc'] }}</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>2000</cbc:ID>
                        <cbc:Name>ISC</cbc:Name>
                        <cbc:TaxTypeCode>EXC</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
        @endif
        @if(floatval($row['total_other_taxes']) > 0)
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_other_taxes'] }}</cbc:TaxAmount>
            <cac:TaxSubtotal>
                <cbc:TaxAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_other_taxes'] }}</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9999</cbc:ID>
                        <cbc:Name>OTROS</cbc:Name>
                        <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
        @endif
        @if(floatval($row['total_plastic_bag_taxes']) > 0)
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_plastic_bag_taxes'] }}</cbc:TaxAmount>
            <cac:TaxSubtotal>
                <cbc:TaxAmount currencyID="{{ $row['currency_type_id'] }}">{{ $row['total_plastic_bag_taxes'] }}</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>7152</cbc:ID>
                        <cbc:Name>ICBPER</cbc:Name>
                        <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
        @endif
    </sac:SummaryDocumentsLine>
    @endforeach
</SummaryDocuments>
