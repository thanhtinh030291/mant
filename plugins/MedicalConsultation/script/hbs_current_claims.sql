SELECT DISTINCT
    clli.clam_oid "clam_oid",
    clli.memb_oid "memb_oid",
    clli.popl_oid "popl_oid",
    --FN_GET_SYS_CODE_DESC(clam.scma_oid_cl_type, 'en') "cl_type",
    TO_CHAR(clli.incur_date_from, 'YYYY-MM-DD') "incur_date_from",
    TO_CHAR(clli.incur_date_to, 'YYYY-MM-DD') "incur_date_to",
    clam.cl_no "cl_no",
    diag.diag_code "diag_code",
    diag.diag_desc "diag_desc",
    behd.ben_head "ben_head",
    FN_GET_SYS_CODE_DESC(behd.scma_oid_ben_type, 'en') "ben_type",
    NVL(prov.prov_name, clli.prov_name) "prov_name",
    clli.pres_amt "pres_amt",
    clli.app_amt "app_amt",
    FN_GET_SYS_CODE(clli.scma_oid_cl_line_status) "status"
FROM cl_line clli
    INNER JOIN cl_claim clam
            ON clli.clam_oid = clam.clam_oid
    INNER JOIN rt_diagnosis diag
            ON clli.diag_oid = diag.diag_oid
    INNER JOIN (
        SELECT
            diag.diag_oid,
            memb.memb_oid,
            MIN(clli.incur_date_from) incur_date_from
        FROM mr_member memb 
            JOIN cl_line clli
              ON memb.memb_oid = clli.memb_oid
            JOIN rt_diagnosis diag
              ON clli.diag_oid = diag.diag_oid
        GROUP BY
            diag.diag_oid,
            memb.memb_oid
    ) diag2
            ON diag.diag_oid = diag2.diag_oid
           AND clli.memb_oid = diag2.memb_oid
           AND clli.incur_date_from = diag2.incur_date_from
    INNER JOIN pd_ben_head behd
            ON clli.behd_oid = behd.behd_oid
     LEFT JOIN pv_provider prov
            ON clli.prov_oid = prov.prov_oid
WHERE clam.cl_no = TO_NUMBER(?)
  AND REGEXP_REPLACE(clam.barcode, '[^0-9]', '') LIKE ?
  AND clli.scma_oid_cl_line_status NOT IN (
    'CL_LINE_STATUS_CL',
    'CL_LINE_STATUS_RV',
    'CL_LINE_STATUS_UE'
  )