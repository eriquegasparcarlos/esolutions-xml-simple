@php echo '<?xml version="1.0" encoding="utf-8" standalone="no"?>'; @endphp
<DespatchAdvice xmlns="urn:oasis:names:specification:ubl:schema:xsd:DespatchAdvice-2"
                xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
                xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema"
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
    <cbc:DespatchAdviceTypeCode listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01"
                                listName="Tipo de Documento"
                                listAgencyName="PE:SUNAT">{{ $document['document_type_id'] }}</cbc:DespatchAdviceTypeCode>
    @if($document['observations'])
        <cbc:Note><![CDATA[{{ $document['observations'] }}]]></cbc:Note>
    @endif
    {{-- Firma: el emisor es el transportista --}}
    <cac:Signature>
        <cbc:ID>{{ $document['signature_uri'] }}</cbc:ID>
        <cbc:Note>{{ $document['signature_note'] }}</cbc:Note>
        <cac:SignatoryParty>
            <cac:PartyIdentification>
                <cbc:ID>{{ $document['carrier_number'] }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[{{ $document['carrier_name'] }}]]></cbc:Name>
            </cac:PartyName>
        </cac:SignatoryParty>
        <cac:DigitalSignatureAttachment>
            <cac:ExternalReference>
                <cbc:URI>#{{ $document['signature_uri'] }}</cbc:URI>
            </cac:ExternalReference>
        </cac:DigitalSignatureAttachment>
    </cac:Signature>
    {{-- Emisor: transportista --}}
    <cac:DespatchSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06"
                        schemeAgencyName="PE:SUNAT"
                        schemeName="Documento de Identidad"
                        schemeID="6">{{ $document['carrier_number'] }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[{{ $document['carrier_name'] }}]]></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:DespatchSupplierParty>
    {{-- Destinatario --}}
    <cac:DeliveryCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06"
                        schemeAgencyName="PE:SUNAT"
                        schemeName="Documento de Identidad"
                        schemeID="{{ $document['customer_identity_document_type_id'] }}">{{ $document['customer_number'] }}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[{{ $document['customer_name'] }}]]></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:DeliveryCustomerParty>
    {{-- Datos del traslado --}}
    <cac:Shipment>
        <cbc:ID>1</cbc:ID>
        <cbc:GrossWeightMeasure unitCode="{{ $document['weight_unit_type_id'] }}">{{ $document['total_weight'] }}</cbc:GrossWeightMeasure>
        @if($document['packages_number'])
            <cbc:TotalTransportHandlingUnitQuantity>{{ $document['packages_number'] }}</cbc:TotalTransportHandlingUnitQuantity>
        @endif
        <cac:ShipmentStage>
            <cbc:TransportModeCode listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo18"
                                   listName="Modalidad de traslado"
                                   listAgencyName="PE:SUNAT">{{ $document['transport_mode_type_id'] }}</cbc:TransportModeCode>
            <cac:TransitPeriod>
                <cbc:StartDate>{{ $document['date_of_shipping'] }}</cbc:StartDate>
            </cac:TransitPeriod>
            {{-- Transportista --}}
            <cac:CarrierParty>
                <cac:PartyIdentification>
                    <cbc:ID schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06"
                            schemeAgencyName="PE:SUNAT"
                            schemeName="Documento de Identidad"
                            schemeID="6">{{ $document['carrier_number'] }}</cbc:ID>
                </cac:PartyIdentification>
                <cac:PartyLegalEntity>
                    <cbc:RegistrationName><![CDATA[{{ $document['carrier_name'] }}]]></cbc:RegistrationName>
                    @if($document['carrier_mtc_number'])
                        <cbc:CompanyID>{{ $document['carrier_mtc_number'] }}</cbc:CompanyID>
                    @endif
                </cac:PartyLegalEntity>
            </cac:CarrierParty>
            {{-- Conductor principal --}}
            <cac:DriverPerson>
                <cbc:ID schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06"
                        schemeAgencyName="PE:SUNAT"
                        schemeName="Documento de Identidad"
                        schemeID="{{ $document['driver_identity_document_type_id'] }}">{{ $document['driver_number'] }}</cbc:ID>
                <cbc:FirstName><![CDATA[{{ $document['driver_first_name'] }}]]></cbc:FirstName>
                <cbc:FamilyName><![CDATA[{{ $document['driver_family_name'] }}]]></cbc:FamilyName>
                <cbc:JobTitle>Principal</cbc:JobTitle>
                <cac:IdentityDocumentReference>
                    <cbc:ID>{{ $document['driver_license_number'] }}</cbc:ID>
                </cac:IdentityDocumentReference>
            </cac:DriverPerson>
            {{-- Conductores secundarios --}}
            @foreach($document['secondary_drivers'] ?? [] as $drv)
                <cac:DriverPerson>
                    <cbc:ID schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06"
                            schemeAgencyName="PE:SUNAT"
                            schemeName="Documento de Identidad"
                            schemeID="{{ $drv['identity_document_type_id'] }}">{{ $drv['number'] }}</cbc:ID>
                    <cbc:FirstName><![CDATA[{{ $drv['first_name'] }}]]></cbc:FirstName>
                    <cbc:FamilyName><![CDATA[{{ $drv['family_name'] }}]]></cbc:FamilyName>
                    <cbc:JobTitle>Secundario</cbc:JobTitle>
                    <cac:IdentityDocumentReference>
                        <cbc:ID>{{ $drv['license_number'] }}</cbc:ID>
                    </cac:IdentityDocumentReference>
                </cac:DriverPerson>
            @endforeach
        </cac:ShipmentStage>
        <cac:Delivery>
            {{-- Punto de llegada --}}
            <cac:DeliveryAddress>
                <cbc:ID schemeAgencyName="PE:INEI"
                        schemeName="Ubigeos">{{ $document['delivery_location_id'] }}</cbc:ID>
                <cac:AddressLine>
                    <cbc:Line><![CDATA[{{ $document['delivery_address'] }}]]></cbc:Line>
                </cac:AddressLine>
            </cac:DeliveryAddress>
            <cac:Despatch>
                {{-- Punto de partida --}}
                <cac:DespatchAddress>
                    <cbc:ID schemeAgencyName="PE:INEI"
                            schemeName="Ubigeos">{{ $document['origin_location_id'] }}</cbc:ID>
                    <cac:AddressLine>
                        <cbc:Line><![CDATA[{{ $document['origin_address'] }}]]></cbc:Line>
                    </cac:AddressLine>
                </cac:DespatchAddress>
                {{-- Remitente (quien encarga el traslado) --}}
                <cac:DespatchParty>
                    <cac:PartyIdentification>
                        <cbc:ID schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06"
                                schemeAgencyName="PE:SUNAT"
                                schemeName="Documento de Identidad"
                                schemeID="{{ $document['sender_identity_document_type_id'] }}">{{ $document['sender_number'] }}</cbc:ID>
                    </cac:PartyIdentification>
                    <cac:PartyLegalEntity>
                        <cbc:RegistrationName><![CDATA[{{ $document['sender_name'] }}]]></cbc:RegistrationName>
                    </cac:PartyLegalEntity>
                </cac:DespatchParty>
            </cac:Despatch>
        </cac:Delivery>
        {{-- Vehículo principal + secundarios (cada uno con su TUC) --}}
        <cac:TransportHandlingUnit>
            <cac:TransportEquipment>
                <cbc:ID>{{ $document['main_plate_number'] }}</cbc:ID>
                <cac:ApplicableTransportMeans>
                    <cbc:RegistrationNationalityID>{{ $document['main_vehicle_tuc'] }}</cbc:RegistrationNationalityID>
                </cac:ApplicableTransportMeans>
                @foreach($document['secondary_vehicles'] ?? [] as $veh)
                    <cac:AttachedTransportEquipment>
                        <cbc:ID>{{ $veh['plate_number'] }}</cbc:ID>
                        <cac:ApplicableTransportMeans>
                            <cbc:RegistrationNationalityID>{{ $veh['tuc'] }}</cbc:RegistrationNationalityID>
                        </cac:ApplicableTransportMeans>
                    </cac:AttachedTransportEquipment>
                @endforeach
            </cac:TransportEquipment>
        </cac:TransportHandlingUnit>
    </cac:Shipment>
    {{-- Líneas --}}
    @foreach($document['items'] as $line)
        <cac:DespatchLine>
            <cbc:ID>{{ $loop->iteration }}</cbc:ID>
            <cbc:DeliveredQuantity unitCodeListAgencyName="United Nations Economic Commission for Europe"
                                   unitCodeListID="UN/ECE rec 20"
                                   unitCode="{{ $line['unit_type_id'] ?? '' }}">{{ $line['quantity'] }}</cbc:DeliveredQuantity>
            <cac:OrderLineReference>
                <cbc:LineID>{{ $loop->iteration }}</cbc:LineID>
            </cac:OrderLineReference>
            <cac:Item>
                @foreach($line['name_parts'] as $part)
                    <cbc:Description><![CDATA[{{ $part }}]]></cbc:Description>
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
        </cac:DespatchLine>
    @endforeach
</DespatchAdvice>
