#!/usr/bin/env node
/**
 * Contrast Checker - Analyzes Lighthouse accessibility audits for color contrast issues
 * Extracts and reports WCAG AA/AAA violations
 */

const fs = require('fs');
const path = require('path');

const RESULTS_DIR = path.join(__dirname, '../audit-results');
const TIMESTAMP_PATTERN = /^\d{4}-\d{2}-\d{2}T\d{2}-\d{2}-\d{2}/;

// Get latest audit results
const files = fs.readdirSync(RESULTS_DIR)
    .filter(f => f.endsWith('.json') && !f.includes('summary') && !f.includes('contrast'))
    .sort()
    .reverse();

if (files.length === 0) {
    console.error('No audit results found. Run lighthouse-audit.js first.');
    process.exit(1);
}

// Get the latest batch timestamp
const latestTimestamp = files[0].match(/^([^_]+)/)[1];
const latestFiles = files.filter(f => f.startsWith(latestTimestamp));

console.log(`\n🎨 Analyzing Contrast Issues from ${latestFiles.length} pages\n`);

const contrastIssues = [];
let totalIssues = 0;

for (const file of latestFiles) {
    const filePath = path.join(RESULTS_DIR, file);
    const report = JSON.parse(fs.readFileSync(filePath, 'utf8'));

    // Extract URL
    const url = report.finalUrl || report.requestedUrl;

    // Check for color contrast audit
    const contrastAudit = report.audits['color-contrast'];

    if (contrastAudit && contrastAudit.score !== null && contrastAudit.score < 1) {
        const issues = {
            url: url,
            score: contrastAudit.score,
            displayValue: contrastAudit.displayValue,
            description: contrastAudit.description,
            details: []
        };

        if (contrastAudit.details && contrastAudit.details.items) {
            issues.details = contrastAudit.details.items.map(item => ({
                selector: item.node?.selector || 'unknown',
                snippet: item.node?.snippet || '',
                contrastRatio: item.subItems?.items?.[0]?.relatedNode?.snippet || 'N/A',
                explanation: item.node?.explanation || ''
            }));
            totalIssues += issues.details.length;
        }

        contrastIssues.push(issues);

        console.log(`❌ ${url}`);
        console.log(`   Score: ${Math.round(contrastAudit.score * 100)}/100`);
        console.log(`   Issues found: ${issues.details.length}`);

        issues.details.forEach((detail, idx) => {
            console.log(`   ${idx + 1}. ${detail.selector}`);
            if (detail.explanation) {
                console.log(`      ${detail.explanation}`);
            }
        });
        console.log('');
    }
}

// Generate contrast report
const contrastReport = {
    timestamp: new Date().toISOString(),
    pagesAnalyzed: latestFiles.length,
    pagesWithIssues: contrastIssues.length,
    totalIssues: totalIssues,
    issues: contrastIssues
};

const reportPath = path.join(RESULTS_DIR, `${latestTimestamp}_contrast-report.json`);
fs.writeFileSync(reportPath, JSON.stringify(contrastReport, null, 2));

// Generate CSV for contrast issues
const csvPath = path.join(RESULTS_DIR, `${latestTimestamp}_contrast-issues.csv`);
const csvHeader = 'URL,Selector,Snippet,Explanation\n';
const csvRows = [];

contrastIssues.forEach(page => {
    page.details.forEach(detail => {
        csvRows.push(
            `"${page.url}","${detail.selector}","${detail.snippet.replace(/"/g, '""')}","${detail.explanation.replace(/"/g, '""')}"`
        );
    });
});

fs.writeFileSync(csvPath, csvHeader + csvRows.join('\n'));

// Print summary
console.log('='.repeat(80));
console.log('🎨 CONTRAST ANALYSIS SUMMARY');
console.log('='.repeat(80));
console.log(`Pages analyzed: ${latestFiles.length}`);
console.log(`Pages with contrast issues: ${contrastIssues.length}`);
console.log(`Total contrast violations: ${totalIssues}`);
console.log('\nFiles generated:');
console.log(`  Report: ${reportPath}`);
console.log(`  CSV:    ${csvPath}`);
console.log('='.repeat(80) + '\n');

if (contrastIssues.length === 0) {
    console.log('✅ No contrast issues found! All pages meet WCAG AA standards.\n');
}
