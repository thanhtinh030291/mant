SELECT
    claims.id,
    claims.summary,
    REPLACE(mbr_no.value, "-", "") mbr_no,
    cl_no.value cl_no,
    common_id.value common_id
FROM mantis_bug_table claims
    INNER JOIN mantis_custom_field_string_table mbr_no
            ON claims.id = mbr_no.bug_id
           AND mbr_no.field_id = %s
           AND mbr_no.value != ''
    INNER JOIN mantis_custom_field_string_table cl_no
            ON claims.id = cl_no.bug_id
           AND cl_no.field_id = %s
           AND cl_no.value NOT IN ('', '77777')
    INNER JOIN mantis_custom_field_string_table common_id
            ON claims.id = common_id.bug_id
           AND common_id.field_id = %s
           AND common_id.value != ''
     LEFT JOIN mantis_bug_relationship_table relationship
            ON claims.id = relationship.destination_bug_id
     LEFT JOIN mantis_bug_table mc_issue
            ON mc_issue.id = relationship.source_bug_id
           AND mc_issue.project_id = %s
           AND mc_issue.category_id = %s
WHERE relationship.id IS NULL
  AND claims.project_id IN (%s)
  AND claims.status NOT IN (%s)
  AND claims.last_updated > UNIX_TIMESTAMP(
        DATE(NOW() - INTERVAL 3 MONTH)
      )