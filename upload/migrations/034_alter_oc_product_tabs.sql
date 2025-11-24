ALTER TABLE oc_product_tabs ADD active_from DATE NULL AFTER status;
ALTER TABLE oc_product_tabs ADD active_to DATE NULL AFTER active_from;