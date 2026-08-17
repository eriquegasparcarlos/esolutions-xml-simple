@php echo '<?xml version="1.0" encoding="utf-8" standalone="no"?>'; @endphp
<Perception xmlns="urn:sunat:names:specification:ubl:peru:schema:xsd:Perception-1"
           xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
           xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
           xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
           xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
           xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent/>
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.0</cbc:UBLVersionID>
    <cbc:CustomizationID>1.0</cbc:CustomizationID>
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
    <cbc:ID>{{ $document['id'] }}</cbc:ID>
    <cbc:IssueDate>{{ $document['date_of_issue'] }}</cbc:IssueDate>
    <cbc:IssueTime>{{ $document['time_of_issue'] }}</cbc:IssueTime>
    {{-- Agente de percepción (la empresa) --}}
    <cac:AgentParty>
        <cac:PartyIdentification>
            <cbc:ID schemeID="6">{{ $document['company_number'] }}</cbc:ID>
        </cac:PartyIdentification>
        @if($document['company_trade_name'])
        <cac:PartyName>
            <cbc:Name><![CDATA[{{ $document['company_trade_name'] }}]]></cbc:Name>
        </cac:PartyName>
        @endif
        @php $sAddr = $document['establishment'] ?? null; @endphp
        @if(is_array($sAddr))
        <cac:PostalAddress>
            <cbc:ID>{{ $sAddr['location_id'] }}</cbc:ID>
            <cbc:StreetName><![CDATA[{{ $sAddr['address'] }}]]></cbc:StreetName>
            <cbc:CityName><![CDATA[{{ $sAddr['department'] }}]]></cbc:CityName>
            <cbc:CountrySubentity><![CDATA[{{ $sAddr['province'] }}]]></cbc:CountrySubentity>
            <cbc:District><![CDATA[{{ $sAddr['district'] }}]]></cbc:District>
            <cac:Country>
                <cbc:IdentificationCode>{{ $sAddr['country_id'] }}</cbc:IdentificationCode>
            </cac:Country>
        </cac:PostalAddress>
        @endif
        <cac:PartyLegalEntity>
            <cbc:RegistrationName><![CDATA[{{ $document['company_name'] }}]]></cbc:RegistrationName>
        </cac:PartyLegalEntity>
    </cac:AgentParty>
    {{-- Cliente al que se percibe --}}
    <cac:ReceiverParty>
        <cac:PartyIdentification>
            <cbc:ID schemeID="{{ $document['customer_identity_document_type_id'] }}">{{ $document['customer_number'] }}</cbc:ID>
        </cac:PartyIdentification>
        <cac:PartyLegalEntity>
            <cbc:RegistrationName><![CDATA[{{ $document['customer_name'] }}]]></cbc:RegistrationName>
        </cac:PartyLegalEntity>
    </cac:ReceiverParty>
    {{-- Catálogo 22: régimen de percepción --}}
    <sac:SUNATPerceptionSystemCode>{{ $document['perception_type_id'] }}</sac:SUNATPerceptionSystemCode>
    <sac:SUNATPerceptionPercent>{{ $document['perception_percentage'] }}</sac:SUNATPerceptionPercent>
    @if($document['observations'])
        <cbc:Note><![CDATA[{{ $document['observations'] }}]]></cbc:Note>
    @endif
    <cbc:TotalInvoiceAmount currencyID="PEN">{{ $document['total_perception'] }}</cbc:TotalInvoiceAmount>
    <sac:SUNATTotalCashed currencyID="PEN">{{ $document['total_cashed'] }}</sac:SUNATTotalCashed>
    @foreach($document['documents'] as $doc)
    <sac:SUNATPerceptionDocumentReference>
        <cbc:ID schemeID="{{ $doc['document_type_id'] }}">{{ $doc['id'] }}</cbc:ID>
        <cbc:IssueDate>{{ $doc['date_of_issue'] }}</cbc:IssueDate>
        <cbc:TotalInvoiceAmount currencyID="{{ $doc['currency_type_id'] }}">{{ $doc['total'] }}</cbc:TotalInvoiceAmount>
        @foreach($doc['payments'] ?? [] as $payment)
        <cac:Payment>
            <cbc:ID>{{ $loop->iteration }}</cbc:ID>
            <cbc:PaidAmount currencyID="{{ $payment['currency_type_id'] }}">{{ $payment['total'] }}</cbc:PaidAmount>
            <cbc:PaidDate>{{ $payment['date_of_payment'] }}</cbc:PaidDate>
        </cac:Payment>
        @endforeach
        <sac:SUNATPerceptionInformation>
            <sac:SUNATPerceptionAmount currencyID="PEN">{{ $doc['perception_amount'] }}</sac:SUNATPerceptionAmount>
            <sac:SUNATPerceptionDate>{{ $doc['perception_date'] }}</sac:SUNATPerceptionDate>
            <sac:SUNATNetTotalCashed currencyID="PEN">{{ $doc['net_total_cashed'] }}</sac:SUNATNetTotalCashed>
            @if(!empty($doc['exchange_rate']))
            <cac:ExchangeRate>
                <cbc:SourceCurrencyCode>{{ $doc['exchange_rate']['source_currency_id'] }}</cbc:SourceCurrencyCode>
                <cbc:TargetCurrencyCode>{{ $doc['exchange_rate']['target_currency_id'] }}</cbc:TargetCurrencyCode>
                <cbc:CalculationRate>{{ $doc['exchange_rate']['factor'] }}</cbc:CalculationRate>
                <cbc:Date>{{ $doc['exchange_rate']['date'] }}</cbc:Date>
            </cac:ExchangeRate>
            @endif
        </sac:SUNATPerceptionInformation>
    </sac:SUNATPerceptionDocumentReference>
    @endforeach
</Perception>
