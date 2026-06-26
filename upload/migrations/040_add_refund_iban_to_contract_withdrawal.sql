ALTER TABLE `oc_contract_withdrawal`
    ADD COLUMN `refund_iban` VARCHAR(34) NOT NULL DEFAULT '' AFTER `address`;
