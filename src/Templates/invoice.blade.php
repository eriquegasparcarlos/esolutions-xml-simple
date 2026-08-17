@php echo '<?xml version="1.0" encoding="utf-8" standalone="no"?>'; @endphp
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
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
    @if($document['date_of_due'])
        <cbc:DueDate>{{ $document['date_of_due'] }}</cbc:DueDate>
    @endif
    <cbc:InvoiceTypeCode listID="{{ $document['operation_type_id'] }}"
                         listAgencyName="PE:SUNAT"
                         listName="Tipo de Documento"
                         name="Tipo de Operacion"
                         listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01"
                         listSchemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo51">{{ $document['document_type_id'] ?? '' }}</cbc:InvoiceTypeCode>
    @foreach($document['legends'] as $leg)
        <cbc:Note languageLocaleID="{{ $leg['code'] }}"><![CDATA[{{ $leg['value'] }}]]></cbc:Note>
    @endforeach
    <cbc:DocumentCurrencyCode listID="ISO 4217 Alpha"
                              listName="Currency"
                              listAgencyName="United Nations Economic Commission for Europe">{{ $document['currency_type_id'] }}</cbc:DocumentCurrencyCode>
    @if($document['purchase_order'])
        <cac:OrderReference>
            <cbc:ID>{{ $document['purchase_order'] }}</cbc:ID>
        </cac:OrderReference>
    @endif

    @foreach($document['guides'] as $guide)
        <cac:DespatchDocumentReference>
            <cbc:ID>{{ $guide['number'] }}</cbc:ID>
            <cbc:DocumentTypeCode listAgencyName="PE:SUNAT"
                                  listName="Tipo de Documento"
                                  listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01">{{ $guide['document_type_id'] }}</cbc:DocumentTypeCode>
        </cac:DespatchDocumentReference>
    @endforeach

    @foreach($document['related'] as $rel)
        <cac:AdditionalDocumentReference>
            <cbc:ID>{{ $rel['number'] }}</cbc:ID>
            <cbc:DocumentTypeCode listAgencyName="PE:SUNAT"
                                  listName="Identificador de documento relacionado"
                                  listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo12">{{ $rel['document_type_id'] }}</cbc:DocumentTypeCode>
        </cac:AdditionalDocumentReference>
    @endforeach

    @foreach($document['prepayments'] as $prepayment)
        <cac:AdditionalDocumentReference>
            <cbc:ID>{{ $prepayment['number'] }}</cbc:ID>
            <cbc:DocumentTypeCode listAgencyName="PE:SUNAT"
                                  listName="Documento Relacionado"
                                  listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo12">{{ $prepayment['document_type_id'] }}</cbc:DocumentTypeCode>
            <cbc:DocumentStatusCode listAgencyName="PE:SUNAT"
                                    listName="Anticipo">{{ $loop->iteration }}</cbc:DocumentStatusCode>
            <cac:IssuerParty>
                <cac:PartyIdentification>
                    <cbc:ID schemeAgencyName="PE:SUNAT"
                            schemeID="6"
                            schemeName="Documento de Identidad"
                            schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">{{ $document['company_number'] }}</cbc:ID>
                </cac:PartyIdentification>
            </cac:IssuerParty>
        </cac:AdditionalDocumentReference>
    @endforeach

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
    {{-- Supplier --}}
    <cac:AccountingSupplierParty>
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
                @php $sAddr = $document['establishment'] ?? null; @endphp
                @if(is_array($sAddr))
                    <cac:RegistrationAddress>
                        <cbc:ID>{{ $sAddr['location_id'] }}</cbc:ID>
                        <cbc:AddressTypeCode>{{ $sAddr['code'] }}</cbc:AddressTypeCode>
                        @if($sAddr['urbanization'])
                            <cbc:CitySubdivisionName><![CDATA[{{ $sAddr['urbanization'] }}]]></cbc:CitySubdivisionName>
                        @endif
                        <cbc:CityName><![CDATA[{{ $sAddr['province'] }}]]></cbc:CityName>
                        <cbc:CountrySubentity><![CDATA[{{ $sAddr['department'] }}]]></cbc:CountrySubentity>
                        <cbc:District><![CDATA[{{ $sAddr['district'] }}]]></cbc:District>
                        <cac:AddressLine>
                            <cbc:Line><![CDATA[{{ $sAddr['address'] }}]]></cbc:Line>
                        </cac:AddressLine>
                        <cac:Country>
                            <cbc:IdentificationCode>{{ $sAddr['country_id'] }}</cbc:IdentificationCode>
                        </cac:Country>
                    </cac:RegistrationAddress>
                @endif
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>

    {{-- Customer --}}
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="{{ $document['customer_identity_document_type_id'] }}"
                        schemeName="Documento de Identidad"
                        schemeAgencyName="PE:SUNAT"
                        schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">{{ $document['customer_number'] }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[{{ $document['customer_name'] }}]]></cbc:RegistrationName>
                @php $cAddr = $document['customer_address'] ?? null; @endphp
                @if(is_array($cAddr) && $cAddr['address'] && $cAddr['location_id'])
                    <cac:RegistrationAddress>
                        <cbc:ID>{{ $cAddr['location_id'] }}</cbc:ID>
                        <cac:AddressLine>
                            <cbc:Line><![CDATA[{{ $cAddr['address'] }}]]></cbc:Line>
                        </cac:AddressLine>
                        <cac:Country>
                            <cbc:IdentificationCode>{{ $cAddr['country_id'] }}</cbc:IdentificationCode>
                        </cac:Country>
                    </cac:RegistrationAddress>
                @endif
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>

    {{-- Detraction (optional) --}}
    @if(!empty($document['detraction']))
        @php $detraction = $document['detraction']; @endphp
        <cac:PaymentMeans>
            <cbc:ID>Detraccion</cbc:ID>
            <cbc:PaymentMeansCode listName="Medio de pago"
                                  listAgencyName="PE:SUNAT"
                                  listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo59">{{ $detraction['payment_method_type_id'] }}</cbc:PaymentMeansCode>
            <cac:PayeeFinancialAccount>
                <cbc:ID>{{ $detraction['bank_account'] }}</cbc:ID>
            </cac:PayeeFinancialAccount>
        </cac:PaymentMeans>
        <cac:PaymentTerms>
            <cbc:ID>Detraccion</cbc:ID>
            <cbc:PaymentMeansID schemeName="Codigo de detraccion"
                                schemeAgencyName="PE:SUNAT"
                                schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo54">{{ $detraction['detraction_type_id'] }}</cbc:PaymentMeansID>
            <cbc:PaymentPercent>{{ $detraction['percentage'] }}</cbc:PaymentPercent>
            <cbc:Amount currencyID="PEN">{{ $detraction['amount_pen'] }}</cbc:Amount>
        </cac:PaymentTerms>
    @endif

    {{-- Payment terms (optional) --}}
    @if($document['payment_condition_id'] === '01' )
        <cac:PaymentTerms>
            <cbc:ID>FormaPago</cbc:ID>
            <cbc:PaymentMeansID>Contado</cbc:PaymentMeansID>
        </cac:PaymentTerms>
    @else
        <cac:PaymentTerms>
            <cbc:ID>FormaPago</cbc:ID>
            <cbc:PaymentMeansID>Credito</cbc:PaymentMeansID>
            <cbc:Amount currencyID="{{ $document['currency_type_id'] }}">{{ $document['fee_total'] }}</cbc:Amount>
        </cac:PaymentTerms>
        @foreach($document['fee'] as $fee)
            <cac:PaymentTerms>
                <cbc:ID>FormaPago</cbc:ID>
                <cbc:PaymentMeansID>Cuota{{ sprintf("%03d", $loop->iteration) }}</cbc:PaymentMeansID>
                <cbc:Amount currencyID="{{ $fee['currency_type_id'] }}">{{ $fee['amount'] }}</cbc:Amount>
                <cbc:PaymentDueDate>{{ $fee['date_of_due'] }}</cbc:PaymentDueDate>
            </cac:PaymentTerms>
        @endforeach
    @endif

    @if($document['perception'])
        @php $perception = $document['perception']; @endphp
        <cac:PaymentTerms>
            <cbc:ID>Percepcion</cbc:ID>
            <cbc:Amount currencyID="PEN">{{ $perception['amount'] }}</cbc:Amount>
        </cac:PaymentTerms>
    @endif

    @if($document['prepayments'])
        @foreach($document['prepayments'] as $prepayment)
            <cac:PrepaidPayment>
                <cbc:ID>{{ $loop->iteration }}</cbc:ID>
                <cbc:PaidAmount currencyID="{{ $document['currency_type_id'] }}">{{ $prepayment['total'] }}</cbc:PaidAmount>
            </cac:PrepaidPayment>
        @endforeach
    @endif

    @foreach($document['charges'] as $charge)
        @if(floatval($charge['amount']) > 0)
            <cac:AllowanceCharge>
                <cbc:ChargeIndicator>true</cbc:ChargeIndicator>
                <cbc:AllowanceChargeReasonCode>{{ $charge['charge_type_id'] }}</cbc:AllowanceChargeReasonCode>
                <cbc:MultiplierFactorNumeric>{{ $charge['factor'] }}</cbc:MultiplierFactorNumeric>
                <cbc:Amount currencyID="{{ $document['currency_type_id'] }}">{{ $charge['amount'] }}</cbc:Amount>
                <cbc:BaseAmount currencyID="{{ $document['currency_type_id'] }}">{{ $charge['base'] }}</cbc:BaseAmount>
            </cac:AllowanceCharge>
        @endif
    @endforeach

    @foreach($document['discounts'] as $discount)
        @if(floatval($discount['amount']) > 0)
            <cac:AllowanceCharge>
                <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
                <cbc:AllowanceChargeReasonCode>{{ $discount['discount_type_id'] }}</cbc:AllowanceChargeReasonCode>
                <cbc:MultiplierFactorNumeric>{{ $discount['factor'] }}</cbc:MultiplierFactorNumeric>
                <cbc:Amount currencyID="{{ $document['currency_type_id'] }}">{{ $discount['amount'] }}</cbc:Amount>
                <cbc:BaseAmount currencyID="{{ $document['currency_type_id'] }}">{{ $discount['base'] }}</cbc:BaseAmount>
            </cac:AllowanceCharge>
        @endif
    @endforeach

    @if($document['perception'])
        @php $perception = $document['perception']; @endphp
        <cac:AllowanceCharge>
            <cbc:ChargeIndicator>true</cbc:ChargeIndicator>
            <cbc:AllowanceChargeReasonCode>{{ $perception['code'] }}</cbc:AllowanceChargeReasonCode>
            <cbc:MultiplierFactorNumeric>{{ $perception['percentage'] }}</cbc:MultiplierFactorNumeric>
            <cbc:Amount currencyID="PEN">{{ $perception['amount'] }}</cbc:Amount>
            <cbc:BaseAmount currencyID="PEN">{{ $perception['base'] }}</cbc:BaseAmount>
        </cac:AllowanceCharge>
    @endif

    @if($document['retention'])
        @php $retention = $document['retention']; @endphp
        <cac:AllowanceCharge>
            <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
            <cbc:AllowanceChargeReasonCode>{{ $retention['code'] }}</cbc:AllowanceChargeReasonCode>
            <cbc:MultiplierFactorNumeric>{{ $retention['percentage'] }}</cbc:MultiplierFactorNumeric>
            <cbc:Amount currencyID="{{ $document['currency_type_id'] }}">{{ $retention['amount'] }}</cbc:Amount>
            <cbc:BaseAmount currencyID="{{ $document['currency_type_id'] }}">{{ $retention['base'] }}</cbc:BaseAmount>
        </cac:AllowanceCharge>
    @endif

    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_taxes'] }}</cbc:TaxAmount>
        @if(floatval($document['total_isc']) > 0)
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_base_isc'] }}</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_isc'] }}</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>2000</cbc:ID>
                        <cbc:Name>ISC</cbc:Name>
                        <cbc:TaxTypeCode>EXC</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        @endif
        @if(floatval($document['total_taxed']) > 0 && !$document['operation_type_is_exportation'])
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
        @if(floatval($document['total_unaffected_init']) > 0)
            <cac:TaxSubtotal>
                <cbc:TaxableAmount
                    currencyID="{{ $document['currency_type_id'] }}">{{ funcNumberFormatXml($document['total_unaffected_init'] - $document['total_prepayment_unaffected'] ) }}</cbc:TaxableAmount>
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
        @if(floatval($document['total_exonerated_init']) > 0)
            <cac:TaxSubtotal>
                <cbc:TaxableAmount
                    currencyID="{{ $document['currency_type_id'] }}">{{ funcNumberFormatXml($document['total_exonerated_init'] - $document['total_prepayment_exonerated'] ) }}</cbc:TaxableAmount>
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
        @if(floatval($document['total_free']) > 0)
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_free'] }}</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_igv_free'] }}</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9996</cbc:ID>
                        <cbc:Name>GRA</cbc:Name>
                        <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        @endif
        @if(floatval($document['total_exportation_init']) > 0)
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_exportation_init'] }}</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">0.00</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9995</cbc:ID>
                        <cbc:Name>EXP</cbc:Name>
                        <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        @endif
        @if(floatval($document['total_other_taxes']) > 0)
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_other_taxes'] }}</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_base_other_taxes'] }}</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>9999</cbc:ID>
                        <cbc:Name>OTROS</cbc:Name>
                        <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        @endif
        @if(floatval($document['total_plastic_bag_taxes']) > 0)
            {{-- ICBPER a nivel documento: solo TaxAmount (sin TaxableAmount, que no aplica al 7152) --}}
            <cac:TaxSubtotal>
                <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_plastic_bag_taxes'] }}</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cac:TaxScheme>
                        <cbc:ID>7152</cbc:ID>
                        <cbc:Name>ICBPER</cbc:Name>
                        <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        @endif
    </cac:TaxTotal>

    {{-- Monetary totals --}}
    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_value'] }}</cbc:LineExtensionAmount>
        {{-- Total precio de venta (SUNAT lo exige: cod. 3305) = valor de venta + tributos + anticipos --}}
        <cbc:TaxInclusiveAmount currencyID="{{ $document['currency_type_id'] }}">{{ funcNumberFormatXml($document['total_value'] + $document['total_taxes'] + $document['total_prepayment']) }}</cbc:TaxInclusiveAmount>
        @if(floatval($document['total_discount_no_base']) > 0)
            <cbc:AllowanceTotalAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_discount_no_base'] }}</cbc:AllowanceTotalAmount>
        @endif
        @if(floatval($document['total_charge']) > 0)
            <cbc:ChargeTotalAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_charge'] }}</cbc:ChargeTotalAmount>
        @endif
        @if(floatval($document['total_prepayment']) > 0)
            <cbc:PrepaidAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_prepayment'] }}</cbc:PrepaidAmount>
        @endif
        <cbc:PayableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $document['total_payable'] }}</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>

    {{-- Lines --}}
    @foreach($document['items'] as $line)
        <cac:InvoiceLine>
            <cbc:ID>{{ $loop->iteration }}</cbc:ID>
            <cbc:InvoicedQuantity unitCode="{{ $line['unit_type_id'] ?? '' }}"
                                  unitCodeListID="UN/ECE rec 20"
                                  unitCodeListAgencyName="United Nations Economic Commission for Europe">{{ $line['quantity'] }}</cbc:InvoicedQuantity>
            <cbc:LineExtensionAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_value'] }}</cbc:LineExtensionAmount>

            <cac:PricingReference>
                @if($line['price_type_id'] === '01')
                    <cac:AlternativeConditionPrice>
                        <cbc:PriceAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['price_amount_01'] }}</cbc:PriceAmount>
                        <cbc:PriceTypeCode listName="Tipo de Precio"
                                           listAgencyName="PE:SUNAT"
                                           listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16">{{ $line['price_type_id']  }}</cbc:PriceTypeCode>
                    </cac:AlternativeConditionPrice>
                @else
                    <cac:AlternativeConditionPrice>
                        <cbc:PriceAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['price_amount_02'] }}</cbc:PriceAmount>
                        <cbc:PriceTypeCode listName="Tipo de Precio"
                                           listAgencyName="PE:SUNAT"
                                           listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16">{{ $line['price_type_id']  }}</cbc:PriceTypeCode>
                    </cac:AlternativeConditionPrice>
                @endif
            </cac:PricingReference>

            @if($document['detraction'] && $document['operation_type_id'] === '1004')
                @php $detraction = $document['detraction']; @endphp
                <cac:Delivery>
                    <cac:Despatch>
                        <cbc:Instructions>{{ $detraction['trip_detail'] }}</cbc:Instructions>
                        <cac:DespatchAddress>
                            <cbc:ID>{{ $detraction['origin_location_id'] }}</cbc:ID>
                            <cac:AddressLine>
                                <cbc:Line>{{ $detraction['origin_address'] }}</cbc:Line>
                            </cac:AddressLine>
                        </cac:DespatchAddress>
                    </cac:Despatch>
                </cac:Delivery>
                <cac:Delivery>
                    <cac:DeliveryTerms>
                        <cbc:ID>01</cbc:ID>
                        <cbc:Amount currencyID="PEN">{{ $detraction['reference_value_service'] }}</cbc:Amount>
                    </cac:DeliveryTerms>
                </cac:Delivery>
                <cac:Delivery>
                    <cac:DeliveryTerms>
                        <cbc:ID>02</cbc:ID>
                        <cbc:Amount currencyID="PEN">{{ $detraction['reference_value_effective_load'] }}</cbc:Amount>
                    </cac:DeliveryTerms>
                </cac:Delivery>
                <cac:Delivery>
                    <cac:DeliveryTerms>
                        <cbc:ID>03</cbc:ID>
                        <cbc:Amount currencyID="PEN">{{ $detraction['reference_value_payload'] }}</cbc:Amount>
                    </cac:DeliveryTerms>
                </cac:Delivery>
                <cac:Delivery>
                    <cac:DeliveryLocation>
                        <cac:Address>
                            <cbc:ID>{{  $detraction['delivery_location_id'] }}</cbc:ID>
                            <cac:AddressLine>
                                <cbc:Line>{{ $detraction['delivery_address'] }}</cbc:Line>
                            </cac:AddressLine>
                        </cac:Address>
                    </cac:DeliveryLocation>
                </cac:Delivery>
            @endif

            @foreach($line['charges'] as $charge)
                <cac:AllowanceCharge>
                    <cbc:ChargeIndicator>true</cbc:ChargeIndicator>
                    <cbc:AllowanceChargeReasonCode>{{ $charge['charge_type_id'] }}</cbc:AllowanceChargeReasonCode>
                    <cbc:MultiplierFactorNumeric>{{ $charge['factor'] }}</cbc:MultiplierFactorNumeric>
                    <cbc:Amount currencyID="{{ $document['currency_type_id'] }}">{{ $charge['amount'] }}</cbc:Amount>
                    <cbc:BaseAmount currencyID="{{ $document['currency_type_id'] }}">{{ $charge['base'] }}</cbc:BaseAmount>
                </cac:AllowanceCharge>
            @endforeach

            @foreach($line['discounts'] as $discount)
                <cac:AllowanceCharge>
                    <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
                    <cbc:AllowanceChargeReasonCode>{{ $discount['discount_type_id'] }}</cbc:AllowanceChargeReasonCode>
                    <cbc:MultiplierFactorNumeric>{{ $discount['factor'] }}</cbc:MultiplierFactorNumeric>
                    <cbc:Amount currencyID="{{ $document['currency_type_id'] }}">{{ $discount['amount'] }}</cbc:Amount>
                    <cbc:BaseAmount currencyID="{{ $document['currency_type_id'] }}">{{ $discount['base'] }}</cbc:BaseAmount>
                </cac:AllowanceCharge>
            @endforeach

            {{-- Line taxes --}}
            <cac:TaxTotal>
                <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_taxes'] }}</cbc:TaxAmount>
                @if(floatval($line['total_isc']) > 0)
                    <cac:TaxSubtotal>
                        <cbc:TaxableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_base_isc'] }}</cbc:TaxableAmount>
                        <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_isc'] }}</cbc:TaxAmount>
                        <cac:TaxCategory>
                            <cbc:Percent>{{ $line['percentage_isc'] }}</cbc:Percent>
                            <cbc:TierRange>{{ $line['system_isc_type_id'] }}</cbc:TierRange>
                            <cac:TaxScheme>
                                <cbc:ID>2000</cbc:ID>
                                <cbc:Name>ISC</cbc:Name>
                                <cbc:TaxTypeCode>EXC</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                    </cac:TaxSubtotal>
                @endif
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
                @if(floatval($line['total_other_taxes']) > 0)
                    <cac:TaxSubtotal>
                        <cbc:TaxableAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_base_other_taxes'] }}</cbc:TaxableAmount>
                        <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_other_taxes'] }}</cbc:TaxAmount>
                        <cac:TaxCategory>
                            <cbc:Percent>{{ $line['percentage_other_taxes'] }}</cbc:Percent>
                            <cac:TaxScheme>
                                <cbc:ID schemeID="UN/ECE 5153"
                                        schemeName="Codigo de tributos"
                                        schemeAgencyName="PE:SUNAT">9999</cbc:ID>
                                <cbc:Name>OTROS</cbc:Name>
                                <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                    </cac:TaxSubtotal>
                @endif
                @if(floatval($line['total_plastic_bag_taxes']) > 0)
                    <cac:TaxSubtotal>
                        <cbc:TaxAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['total_plastic_bag_taxes'] }}</cbc:TaxAmount>
                        <cbc:BaseUnitMeasure unitCode="NIU">{{ $line['quantity'] }}</cbc:BaseUnitMeasure>
                        <cac:TaxCategory>
                            <cbc:PerUnitAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['amount_plastic_bag_taxes'] }}</cbc:PerUnitAmount>
                            <cac:TaxScheme>
                                <cbc:ID schemeID="UN/ECE 5153"
                                        schemeName="Codigo de tributos"
                                        schemeAgencyName="PE:SUNAT">7152</cbc:ID>
                                <cbc:Name>ICBPER</cbc:Name>
                                <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                    </cac:TaxSubtotal>
                @endif
            </cac:TaxTotal>
            {{-- Item --}}
            <cac:Item>
                @foreach($line['name_parts'] as $part)
                    <cbc:Description>{{ $part }}</cbc:Description>
                @endforeach
                @if($line['internal_id'])
                    <cac:SellersItemIdentification>
                        <cbc:ID>{{ $line['internal_id'] }}</cbc:ID>
                    </cac:SellersItemIdentification>
                @endif
                @if($line['item_code'])
                    <cac:CommodityClassification>
                        <cbc:ItemClassificationCode listID="UNSPSC"
                                                    listAgencyName="GS1 US"
                                                    listName="Item Classification">{{ $line['item_code'] }}</cbc:ItemClassificationCode>
                    </cac:CommodityClassification>
                @endif
                @if($document['operation_type_id'] === '0202')
                    @foreach ($line['accommodation_attributes'] as $attr)
                        @php
                            $attributeTypeId = $attr['attribute_type_id'];
                            $hasPeriod = !empty($attr['period']) && is_array($attr['period']);
                            $period = $attr['period'] ?? [];
                        @endphp
                        <cac:AdditionalItemProperty>
                            <cbc:Name><![CDATA[{{ $attr['name'] }}]]></cbc:Name>
                            <cbc:NameCode listName="Propiedad del item" listAgencyName="PE:SUNAT">{{ $attributeTypeId }}</cbc:NameCode>
                            @if(!in_array($attributeTypeId, ['4002', '4003', '4004', '4005', '4006'], true) && isset($attr['value']))
                                <cbc:Value>{{ $attr['value'] }}</cbc:Value>
                            @endif
                            @if($hasPeriod)
                                @if($attributeTypeId === '4005')
                                    <cac:UsabilityPeriod>
                                        @if(!empty($period['duration_days']))
                                            <cbc:DurationMeasure unitCode="DAY">{{ $period['duration_days'] }}</cbc:DurationMeasure>
                                        @endif
                                    </cac:UsabilityPeriod>
                                @endif
                                @if(in_array($attributeTypeId, ['4002', '4003', '4004', '4006']))
                                    <cac:UsabilityPeriod>
                                        @if(!empty($period['start_date']))
                                            <cbc:StartDate>{{ $period['start_date'] }}</cbc:StartDate>
                                        @endif
                                    </cac:UsabilityPeriod>
                                @endif
                            @endif
                        </cac:AdditionalItemProperty>
                    @endforeach
                @endif

                {{-- Item properties --}}
                @foreach($line['attributes'] as $attr)
                    @php
                        $attributeTypeId = $attr['attribute_type_id'];
                        $hasPeriod = !empty($attr['period']) && is_array($attr['period']);
                        $period = $attr['period'] ?? [];
                    @endphp
                    @if(!empty($attr['name']))
                        <cac:AdditionalItemProperty>
                            <cbc:Name><![CDATA[{{ $attr['name'] }}]]></cbc:Name>
                            <cbc:NameCode listName="Propiedad del item" listAgencyName="PE:SUNAT">{{ $attributeTypeId }}</cbc:NameCode>
                            @if(!in_array($attributeTypeId, ['3005','3006'], true) && isset($attr['value']))
                                <cbc:Value>{{ $attr['value'] }}</cbc:Value>
                            @endif
                            @if(!empty($attr['value_qualifier']))
                                <cbc:ValueQualifier>{{ $attr['value_qualifier'] }}</cbc:ValueQualifier>
                            @endif
                            @if($attributeTypeId === '3006' && isset($attr['value_quantity']))
                                <cbc:ValueQuantity>{{ $attr['value_quantity'] }}</cbc:ValueQuantity>
                            @endif
                            @if($hasPeriod)
                                <cac:UsabilityPeriod>
                                    @if(!empty($period['start_date']))
                                        <cbc:StartDate>{{ $period['start_date'] }}</cbc:StartDate>
                                    @endif
                                    @if(!empty($period['start_time']))
                                        <cbc:StartTime>{{ $period['start_time'] }}</cbc:StartTime>
                                    @endif
                                    @if(!empty($period['end_date']))
                                        <cbc:EndDate>{{ $period['end_date'] }}</cbc:EndDate>
                                    @endif
                                    @if(!empty($period['duration_days']))
                                        <cbc:DurationMeasure unitCode="DAY">{{ $period['duration_days'] }}</cbc:DurationMeasure>
                                    @endif
                                </cac:UsabilityPeriod>
                            @endif
                        </cac:AdditionalItemProperty>
                    @endif
                @endforeach
            </cac:Item>
            <cac:Price>
                <cbc:PriceAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['unit_value'] }}</cbc:PriceAmount>
            </cac:Price>
        </cac:InvoiceLine>
    @endforeach
</Invoice>
