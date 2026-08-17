@php echo '<?xml version="1.0" encoding="utf-8" standalone="no"?>'; @endphp
<CreditNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2"
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
    @foreach($document['legends'] as $leg)
        <cbc:Note languageLocaleID="{{ $leg['code'] }}"><![CDATA[{{ $leg['value'] }}]]></cbc:Note>
    @endforeach
    <cbc:DocumentCurrencyCode listID="ISO 4217 Alpha"
                              listName="Currency"
                              listAgencyName="United Nations Economic Commission for Europe">{{ $document['currency_type_id'] }}</cbc:DocumentCurrencyCode>
    {{-- Motivo de la nota (catálogo 09) --}}
    <cac:DiscrepancyResponse>
        <cbc:ReferenceID>{{ $document['affected_document']['id'] }}</cbc:ReferenceID>
        <cbc:ResponseCode listAgencyName="PE:SUNAT"
                          listName="Tipo de nota de credito"
                          listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo09">{{ $document['note_type_id'] }}</cbc:ResponseCode>
        <cbc:Description><![CDATA[{{ $document['note_description'] }}]]></cbc:Description>
    </cac:DiscrepancyResponse>
    @if($document['purchase_order'])
        <cac:OrderReference>
            <cbc:ID>{{ $document['purchase_order'] }}</cbc:ID>
        </cac:OrderReference>
    @endif
    {{-- Comprobante afectado --}}
    <cac:BillingReference>
        <cac:InvoiceDocumentReference>
            <cbc:ID>{{ $document['affected_document']['id'] }}</cbc:ID>
            <cbc:DocumentTypeCode listAgencyName="PE:SUNAT"
                                  listName="Tipo de Documento"
                                  listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01">{{ $document['affected_document']['document_type_id'] }}</cbc:DocumentTypeCode>
        </cac:InvoiceDocumentReference>
    </cac:BillingReference>

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

    {{-- Forma de pago: en una nota de crédito SUNAT solo admite cac:PaymentTerms
         para informar las cuotas del tipo 13 (ajuste de montos/fechas de pago) a
         crédito. Emitirlo con "Contado" —como en una factura— hace que la nota
         sea rechazada con 3246 ("el tipo de transaccion o el identificador de la
         cuota no cumple con el formato esperado"), verificado contra SUNAT beta
         y contra el OSE de Nubefact. --}}
    @if($document['note_type_id'] === '13' && $document['payment_condition_id'] !== '01')
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
        {{-- Total precio de venta (SUNAT cod. 3305) = valor de venta + tributos + anticipos --}}
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
        <cac:CreditNoteLine>
            <cbc:ID>{{ $loop->iteration }}</cbc:ID>
            <cbc:CreditedQuantity unitCode="{{ $line['unit_type_id'] ?? '' }}"
                                  unitCodeListID="UN/ECE rec 20"
                                  unitCodeListAgencyName="United Nations Economic Commission for Europe">{{ $line['quantity'] }}</cbc:CreditedQuantity>
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

            {{-- Line taxes: en CreditNoteLine el TaxTotal va ANTES de AllowanceCharge (orden XSD, inverso a InvoiceLine) --}}
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
            </cac:Item>
            <cac:Price>
                <cbc:PriceAmount currencyID="{{ $document['currency_type_id'] }}">{{ $line['unit_value'] }}</cbc:PriceAmount>
            </cac:Price>
        </cac:CreditNoteLine>
    @endforeach
</CreditNote>
