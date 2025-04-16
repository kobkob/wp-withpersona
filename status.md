# Inquiry Status Documentation

## Overview

This document outlines the possible status values for an Inquiry in the Persona system.

## Status Values

| Status         | Description                                    |
| -------------- | ---------------------------------------------- |
| `created`      | Initial state when an inquiry is first created |
| `pending`      | Inquiry is awaiting processing or user action  |
| `completed`    | Inquiry has been successfully processed        |
| `expired`      | Inquiry has exceeded its validity period       |
| `failed`       | Inquiry processing encountered an error        |
| `needs_review` | Inquiry requires manual review                 |
| `approved`     | Inquiry has been approved                      |
| `declined`     | Inquiry has been declined                      |

## Important Note

⚠️ **Status values are not static**: Persona may introduce new status values at any time without a versioned update. Your implementation should be flexible enough to handle additional status values in the future.
