import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../model/complaint_model.dart';
import '../../provider/complaint_provider.dart';

class ComplaintListScreen extends StatefulWidget {
  const ComplaintListScreen({super.key});

  @override
  State<ComplaintListScreen> createState() => _ComplaintListScreenState();
}

class _ComplaintListScreenState extends State<ComplaintListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ComplaintProvider>().loadComplaints();
    });
  }

  Color _statusColor(String s) {
    switch (s) {
      case 'resolved':
      case 'closed':
        return AppColors.success;
      case 'in_progress':
        return AppColors.warning;
      default:
        return AppColors.secondary;
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<ComplaintProvider>();
    return Scaffold(
      appBar: AppBar(title: Text('complaint.my_complaints'.tr())),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/create-complaint'),
        icon: const Icon(Icons.add),
        label: Text('complaint.new'.tr()),
      ),
      body: p.loading && p.complaints.isEmpty
          ? const LoadingIndicator()
          : p.complaints.isEmpty
              ? EmptyState(
                  icon: Icons.report_problem_outlined,
                  title: 'complaint.no_complaints'.tr(),
                  message: 'complaint.tap_new_to_raise'.tr())
              : RefreshIndicator(
                  onRefresh: () => p.loadComplaints(),
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: p.complaints.map(_tile).toList(),
                  ),
                ),
    );
  }

  Widget _tile(ComplaintModel c) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(c.category,
                    style: const TextStyle(fontWeight: FontWeight.bold)),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: _statusColor(c.status).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(c.status.replaceAll('_', ' ').toUpperCase(),
                    style: TextStyle(
                        color: _statusColor(c.status),
                        fontSize: 11,
                        fontWeight: FontWeight.bold)),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(c.description),
          if (c.response?.isNotEmpty ?? false) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: AppColors.background,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text('complaint.reply'.tr(namedArgs: {'text': c.response ?? ''}),
                  style: const TextStyle(fontSize: 13)),
            ),
          ],
          const SizedBox(height: 6),
          Text(Helpers.dateTime(c.createdAt),
              style: const TextStyle(
                  fontSize: 11, color: AppColors.textSecondary)),
        ],
      ),
    );
  }
}
