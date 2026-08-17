@php echo '<?xml version="1.0" encoding="utf-8" standalone="no"?>'; @endphp
<SelfBilledInvoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:SelfBilledInvoice-2"
                   xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
                   xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
                   xmlns:ccts="urn:un:unece:uncefact:documentation:2"
                   xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                   xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
                   xmlns:qdt="urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2"
                   xmlns:udt="urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent/>
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>2.0</cbc:CustomizationID>
    <cbc:ID>{{ $document['id'] }}</cbc:ID>
    <cbc:IssueDate>{{ $document['date_of_issue'] }}</cbc:IssueDate>
    <cbc:IssueTime>{{ $document['time_of_issue'] }}</cbc:IssueTime>
    <cbc:InvoiceTypeCode listID="{{ $document['operation_type_id'] }}"
                         listAgencyName="PE:SUNAT"
                         listName="Tipo de Documento"
                         name="Tipo de Operacion"
                         listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01"
                         listSchemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo51">{{ $document['document_type_id'] ?? '04' }}</cbc:InvoiceTypeCode>
    @foreach($document['legends'] as $leg)
        <cbc:Note languageLocaleID="{{ $leg['code'] }}"><![CDATA[{{ $leg['value'] }}]]></cbc:Note>
    @endforeach
    <cbc:DocumentCurrencyCode listID="ISO 4217 Alpha"
                              listName="Currency"
                              listAgencyName="United Nations Economic Commission for Europe">{{ $document['currency_type_id'] }}</cbc:DocumentCurrencyCode>

    {{-- Signature metadata --}}
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

    {{-- Customer = la EMPRESA que emite la liquidación (comprador con RUC).
         En una auto-facturación (SelfBilledInvoice) el "customer" es quien se
         auto-factura. El XSD ordena Customer ANTES que Supplier. --}}
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="{{ $document['company_identity_document_type_id'] }}"
                        schemeName="Documento de Identidad"
                        schemeAgencyName="PE:SUNAT"
                        schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">{{ $document['company_number'] }}</cbc:ID>
            </cac:PartyIdentification>
            @if($document['company_trade_name'])
                <cac:PartyName>
                    <cbc:Name><![CDATA[{{ $document['company_trade_name'] }}]]></cbc:Name>
                </cac:PartyName>
            @endif
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[{{ $document['company_name'] }}]]></cbc:RegistrationName>
                @php $eAddr = $document['establishment'] ?? null; @endphp
                @if(is_array($eAddr))
                    <cac:RegistrationAddress>
                        <cbc:ID>{{ $eAddr['location_id'] }}</cbc:ID>
                        <cbc:AddressTypeCode>{{ $eAddr['code'] }}</cbc:AddressTypeCode>
                        @if($eAddr['urbanization'])
                            <cbc:CitySubdivisionName><![CDATA[{{ $eAddr['urbanization'] }}]]></cbc:CitySubdivisionName>
                        @endif
                        <cbc:CityName><![CDATA[{{ $eAddr['province'] }}]]></cbc:CityName>
                        <cbc:CountrySubentity><![CDATA[{{ $eAddr['department'] }}]]></cbc:CountrySubentity>
                        <cbc:District><![CDATA[{{ $eAddr['district'] }}]]></cbc:District>
                        <cac:AddressLine>
                            <cbc:Line><![CDATA[{{ $eAddr['address'] }}]]></cbc:Line>
                        </cac:AddressLine>
                        <cac:Country>
                            <cbc:IdentificationCode>{{ $eAddr['country_id'] }}</cbc:IdentificationCode>
                        </cac:Country>
                    </cac:RegistrationAddress>
                @endif
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>

    {{-- Supplier = el PROVEEDOR (vendedor sin RUC, persona natural: DNI u otro
         documento del catálogo 06). Es a quien se le compra. --}}
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="{{ $document['supplier_identity_document_type_id'] }}"
                        schemeName="Documento de Identidad"
                        schemeAgencyName="PE:SUNAT"
                        schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">{{ $document['supplier_number'] }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[{{ $document['supplier_name'] }}]]></cbc:RegistrationName>
                @php $pAddr = $document['supplier_address'] ?? null; @endphp
                @if(is_array($pAddr) && !empty($pAddr['location_id']))
                    <cac:RegistrationAddress>
                        <cbc:ID>{{ $pAddr['location_id'] }}</cbc:ID>
                        @if(!empty($pAddr['address_type_code']))
                            <cbc:AddressTypeCode>{{ $pAddr['address_type_code'] }}</cbc:AddressTypeCode>
                        @endif
                        <cbc:CityName><![CDATA[{{ $pAddr['province'] ?? '' }}]]></cbc:CityName>
                        <cbc:CountrySubentity><![CDATA[{{ $pAddr['department'] ?? '' }}]]></cbc:CountrySubentity>
                        <cbc:District><![CDATA[{{ $pAddr['district'] ?? '' }}]]></cbc:District>
                        <cac:AddressLine>
                            <cbc:Line><![CDATA[{{ $pAddr['address'] ?? '' }}]]></cbc:Line>
                        </cac:AddressLine>
                        <cac:Country>
                            <cbc:IdentificationCode>{{ $pAddr['country_id'] ?? 'PE' }}</cbc:IdentificationCode>
                        </cac:Country>
                    </cac:RegistrationAddress>
                @endif
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>

    {{-- Lugar de la operación (dónde se adquirieron los bienes). SUNAT lo exige
         para la liquidación de compra de productos primarios. --}}
    @php $op = $document['operation'] ?? null; @endphp
    @if(is_array($op) && !empty($op['location_id']))
        <cac:DeliveryTerms>
            <cac:DeliveryLocation>
                @if(!empty($op['location_type_code']))
                    <cbc:LocationTypeCode>{{ $op['location_type_code'] }}</cbc:LocationTypeCode>
                @endif
                <cac:Address>
                    <cbc:ID>{{ $op['location_id'] }}</cbc:ID>
                    <cbc:CityName><![CDATA[{{ $op['province'] ?? '' }}]]></cbc:CityName>
                    <cbc:CountrySubentity><![CDATA[{{ $op['department'] ?? '' }}]]></cbc:CountrySubentity>
                    <cbc:District><![CDATA[{{ $op['district'] ?? '' }}]]></cbc:District>
                    <cac:AddressLine>
                        <cbc:Line><![CDATA[{{ $op['address'] ?? '' }}]]></cbc:Line>
                    </cac:AddressLine>
                    <cac:Country>
                        <cbc:IdentificationCode>{{ $op['country_id'] ?? 'PE' }}</cbc:IdentificationCode>
                    </cac:Country>
                </cac:Address>
            </cac:DeliveryLocation>
        </cac:DeliveryTerms>
    @endif

    {{-- Forma de pago (Contado por defecto; Crédito con cuotas si aplica). --}}
    @if(($document['payment_condition_id'] ?? '01') === '01')
        <cac:PaymentTerms>
            <cbc:ID>FormaPago</cbc:ID>
            <cbc:PaymentMeansID>Contado</cbc:PaymentMeansID>
        </cac:PaymentTerms>
    @else
        <cac:PaymentTerms>
            <cbc:ID>FormaPago</cbc:ID>
            <cbc:PaymentMeansID>Credito</cbc:PaymentMeansID>
            <cbc:Amount currencyID="{{ $document['currency_type_id'] }}">{{ $document['fee_total'] ?? $document['total_payable'] }}</cbc:Amount>
        </cac:PaymentTerms>
        @foreach(($document['fee'] ?? []) as $fee)
            <cac:PaymentTerms>
                <cbc:ID>FormaPago</cbc:ID>
                <cbc:PaymentMeansID>Cuota{{ sprintf("%03d", $loop->iteration) }}</cbc:PaymentMeansID>
                <cbc:Amount currencyID="{{ $fee['currency_type_id'] }}">{{ $fee['amount'] }}</cbc:Amount>
                <cbc:PaymentDueDate>{{ $fee['date_of_due'] }}</cbc:PaymentDueDate>
            </cac:PaymentTerms>
        @endforeach
    @endif

    {{-- Totales de impuestos (cabecera) --}}
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_taxes'] }}</cbc:TaxAmount>
        @if(floatval($document['total_taxed']) > 0)
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_taxed'] }}</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_igv'] }}</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>1000</cbc:ID>
                        <cbc:Name>IGV</cbc:Name>
                        <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        @endif
        @if(floatval($document['total_exonerated']) > 0)
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_exonerated'] }}</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">0.00</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9997</cbc:ID>
                        <cbc:Name>EXO</cbc:Name>
                        <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        @endif
        @if(floatval($document['total_unaffected']) > 0)
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_unaffected'] }}</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">0.00</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9998</cbc:ID>
                        <cbc:Name>INA</cbc:Name>
                        <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        @endif
    </cac:TaxTotal>

    {{-- Totales monetarios --}}
    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_value'] }}</cbc:LineExtensionAmount>
        <cbc:TaxInclusiveAmount currencyID="{{ $document['currency_type_id'] }}">{{ funcNumberFormatXml($document['total_value'] + $document['total_taxes']) }}</cbc:TaxInclusiveAmount>
        <cbc:PayableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_payable'] }}</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>

    {{-- Líneas --}}
    @foreach($document['items'] as $line)
        <cac:InvoiceLine>
            <cbc:ID>{{ $loop->iteration }}</cbc:ID>
            <cbc:InvoicedQuantity unitCode="{{ $line['unit_type_id'] ?? '' }}"
                                  unitCodeListID="UN/ECE rec 20"
                                  unitCodeListAgencyName="United Nations Economic Commission for Europe">{{ $line['quantity'] }}</cbc:InvoicedQuantity>
            <cbc:LineExtensionAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_value'] }}</cbc:LineExtensionAmount>

            <cac:PricingReference>
                <cac:AlternativeConditionPrice>
                    <cbc:PriceAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['price_amount_01'] ?? $line['unit_price'] ?? $line['unit_value'] }}</cbc:PriceAmount>
                    <cbc:PriceTypeCode listName="Tipo de Precio"
                                       listAgencyName="PE:SUNAT"
                                       listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16">{{ $line['price_type_id'] ?? '01' }}</cbc:PriceTypeCode>
                </cac:AlternativeConditionPrice>
            </cac:PricingReference>

            {{-- Impuestos de línea --}}
            <cac:TaxTotal>
                <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_taxes'] }}</cbc:TaxAmount>
                <cac:TaxSubtotal>
                    <cbc:TaxableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_base_igv'] }}</cbc:TaxableAmount>
                    <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_igv'] }}</cbc:TaxAmount>
                    <cac:TaxCategory>
                        <cbc:Percent>{{ $line['percentage_igv'] }}</cbc:Percent>
                        <cbc:TaxExemptionReasonCode>{{ $line['affectation_igv_type_id'] }}</cbc:TaxExemptionReasonCode>
                        @php $affectation = \ESolutions\XmlSimple\Support\FunctionTribute::getByAffectation($line['affectation_igv_type_id']); @endphp
                        <cac:TaxScheme>
                            <cbc:ID schemeID="UN/ECE 5153"
                                    schemeName="Codigo de tributos"
                                    schemeAgencyName="PE:SUNAT">{{ $affectation['id'] }}</cbc:ID>
                            <cbc:Name>{{ $affectation['name'] }}</cbc:Name>
                            <cbc:TaxTypeCode>{{ $affectation['code'] }}</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                </cac:TaxSubtotal>
            </cac:TaxTotal>

            {{-- Ítem --}}
            <cac:Item>
                @foreach($line['name_parts'] as $part)
                    <cbc:Description><![CDATA[{{ $part }}]]></cbc:Description>
                @endforeach
                @if(!empty($line['internal_id']))
                    <cac:SellersItemIdentification>
                        <cbc:ID>{{ $line['internal_id'] }}</cbc:ID>
                    </cac:SellersItemIdentification>
                @endif
                @if(!empty($line['item_code']))
                    <cac:CommodityClassification>
                        <cbc:ItemClassificationCode listID="UNSPSC"
                                                    listAgencyName="GS1 US"
                                                    listName="Item Classification">{{ $line['item_code'] }}</cbc:ItemClassificationCode>
                    </cac:CommodityClassification>
                @endif
            </cac:Item>
            <cac:Price>
                <cbc:PriceAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['unit_value'] }}</cbc:PriceAmount>
            </cac:Price>
        </cac:InvoiceLine>
    @endforeach
</SelfBilledInvoice>
